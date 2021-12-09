<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Core\Shared\Encryptor;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\ResponseEmitter;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\View\GlobalViewContext;
use Snicco\Core\Application\Application;
use Snicco\Session\Events\NewLogin;
use Snicco\Auth\AuthServiceProvider;
use Snicco\Session\Events\NewLogout;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Middleware\VerifyCsrfToken;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Session\Listeners\InvalidateSession;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Session\Drivers\DatabaseSessionDriver;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\Session\Middleware\StartSessionMiddleware;

class SessionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->config->extend('session.enabled', false);
        
        if ( ! $this->config->get('session.enabled')) {
            return;
        }
        
        $this->bindConfig();
        $this->bindSessionDriver();
        $this->bindSessionManager();
        $this->bindSession();
        $this->bindAliases();
        $this->bindEvents();
        $this->bindListeners();
        $this->bindCsrfField();
        $this->bindStatefulRedirector();
        $this->bindSessionMiddleware();
    }
    
    function bootstrap() :void
    {
        if ( ! $this->config->get('session.enabled')) {
            return;
        }
        
        $this->bindViewContext();
    }
    
    private function bindConfig()
    {
        // misc
        $this->config->extend('session.table', 'sessions');
        $this->config->extend('session.lottery', [2, 100]);
        $this->config->extend('session.driver', 'database');
        $this->config->extend('session.encrypt', false);
        
        // cookie
        $this->config->extend('session.cookie', 'snicco_test_session');
        $this->config->extend('session.path', '/');
        $this->config->extend('session.domain', null);
        $this->config->extend('session.secure', true);
        $this->config->extend('session.http_only', true);
        $this->config->extend('session.same_site', 'lax');
        
        // lifetime
        $this->config->extend('session.lifetime', SessionManager::HOUR_IN_SEC * 8);
        $this->config->extend('session.rotate', $this->config->get('session.lifetime') / 2);
        
        // middleware
        $this->config->extend('middleware.aliases', [
            'csrf' => VerifyCsrfToken::class,
        ]);
        $this->config->extend('middleware.groups.global', [
            StartSessionMiddleware::class,
        ]);
        
        $this->config->extend('middleware.priority', [
            StartSessionMiddleware::class,
        ]);
    }
    
    private function bindSessionDriver()
    {
        $this->container->singleton(SessionDriver::class, function () {
            $name = $this->config->get('session.driver', 'database');
            $lifetime = $this->config->get('session.lifetime');
            
            if ($name === 'database') {
                global $wpdb;
                
                $table = $this->config->get('session.table');
                
                return new DatabaseSessionDriver($wpdb, $table, $lifetime);
            }
            
            if ($name === 'array' || $this->app->isRunningUnitTest()) {
                return new ArraySessionDriver($lifetime);
            }
        });
    }
    
    private function bindSessionManager()
    {
        $this->container->singleton(SessionManager::class, function () {
            return new SessionManager(
                $this->config->get('session'),
                $this->container[Session::class],
                $this->container[Dispatcher::class]
            );
        });
        
        $this->container->singleton(SessionManagerInterface::class, function () {
            return $this->container[SessionManager::class];
        });
    }
    
    private function bindSession()
    {
        $this->container->singleton(Session::class, function () {
            if ($this->config->get('session.encrypt')) {
                $store = new EncryptedSession(
                    $this->container->get(SessionDriver::class),
                    $this->container->get(Encryptor::class)
                );
            }
            else {
                $store = new Session($this->container->get(SessionDriver::class));
            }
            
            $this->container->instance(Session::class, $store);
            
            return $store;
        });
    }
    
    private function bindAliases()
    {
        /** @var Application $app */
        $app = $this->container->get(Application::class);
        
        $app->alias('session', Session::class);
        $app->alias('csrfField', CsrfField::class, 'asHtml');
        $app->alias('csrf', CsrfField::class);
    }
    
    private function bindEvents()
    {
        if (in_array(AuthServiceProvider::class, $this->config->get('app.providers', []))) {
            return;
        }
        
        $this->config->extend('events.mapped', [
            'wp_login' => [NewLogin::class],
            'wp_logout' => [NewLogout::class],
        ]);
        
        $this->config->extend('events.listeners', [
            NewLogin::class => [
                [InvalidateSession::class, 'handleLogin'],
            ],
            NewLogout::class => [
                [InvalidateSession::class, 'handleLogout'],
            ],
        ]);
    }
    
    private function bindStatefulRedirector()
    {
        if ( ! $this->sessionEnabled()) {
            return;
        }
        
        $this->container->singleton(Redirector::class, function () {
            return new StatefulRedirector(
                $this->container[Session::class],
                $this->container[UrlGenerator::class],
                $this->container[ResponseFactoryInterface::class]
            );
        });
    }
    
    private function bindViewContext()
    {
        if ( ! $this->container->has(GlobalViewContext::class)) {
            return;
        }
        
        /** @var GlobalViewContext $global_context */
        $global_context = $this->container->get(GlobalViewContext::class);
        
        $global_context->add('csrf', function () {
            return $this->container->get(CsrfField::class);
        });
        $global_context->add('session', function () {
            return $this->container->get(Session::class);
        });
        $global_context->add('errors', function () {
            return $this->container->get(Session::class)
                                   ->errors();
        });
    }
    
    private function bindListeners()
    {
        $this->container->singleton(InvalidateSession::class, function () {
            return new InvalidateSession(
                $this->container[Request::class],
                $this->container[ResponseEmitter::class],
                $this->container[SessionManagerInterface::class],
                $this->container[Session::class]
            );
        });
    }
    
    private function bindCsrfField()
    {
        $this->container->singleton(CsrfField::class, function () {
            return new CsrfField($this->container[Session::class]);
        });
    }
    
    private function bindSessionMiddleware()
    {
        $this->container->singleton(StartSessionMiddleware::class, function () {
            return new StartSessionMiddleware($this->container[SessionManagerInterface::class]);
        });
    }
    
}