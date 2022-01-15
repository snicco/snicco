<?php

declare(strict_types=1);

namespace Snicco\SessionBundle;

use LogicException;
use Psr\Log\LoggerInterface;
use Snicco\View\GlobalViewContext;
use Snicco\Session\SessionManager;
use Snicco\Auth\AuthServiceProvider;
use Snicco\Session\Contracts\SessionDriver;
use Snicco\Session\Drivers\EncryptedDriver;
use Snicco\HttpRouting\Http\ResponseEmitter;
use Snicco\Session\ValueObjects\SessionConfig;
use Snicco\Session\Contracts\SessionEncryptor;
use Snicco\Session\Drivers\ArraySessionDriver;
use Snicco\Session\Drivers\WPObjectCacheDriver;
use Snicco\Session\EventDispatcherUsingWPHooks;
use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Session\Drivers\DatabaseSessionDriver;
use Snicco\SessionBundle\Middleware\StartSession;
use Snicco\Component\Core\Contracts\ServiceProvider;
use Snicco\SessionBundle\Middleware\VerifyCsrfToken;
use Snicco\Session\Contracts\SessionEventDispatcher;
use Snicco\Session\Contracts\SessionManagerInterface;
use Snicco\SessionBundle\Middleware\AllowMutableSession;
use Snicco\SessionBundle\Middleware\HandleStatefulRequest;
use Snicco\SessionBundle\Middleware\ShareSessionWithViews;
use Snicco\SessionBundle\BetterWPHooks\Events\UserLoggedIn;
use Snicco\SessionBundle\BetterWPHooks\Events\UserLoggedOut;
use Snicco\Session\ValueObjects\ClockUsingDateTimeImmutable;
use Snicco\SessionBundle\BetterWPHooks\Listeners\InvalidateSession;
use Snicco\SessionBundle\Middleware\AddResponseAttributesToSession;
use Snicco\SessionBundle\BetterWPHooks\SessionEventDispatcherUsingBetterWPHooks;

use function is_null;
use function in_array;
use function is_string;

/**
 * @interal
 */
class SessionServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindSessionDriver();
        $this->bindSessionManager();
        $this->bindEvents();
        $this->bindSessionEventDispatcher();
        $this->bindMiddlewares();
    }
    
    function bootstrap() :void
    {
        //
    }
    
    private function bindConfig()
    {
        $this->container->singleton(SessionConfig::class, function () {
            return new SessionConfig($this->config->get('session'));
        });
        
        // misc
        $this->config->extend('session.table', 'sessions');
        $this->config->extend('session.encrypt', false);
        
        // middleware+
        $this->config->extend('middleware.aliases', [
            'csrf' => VerifyCsrfToken::class,
            'session.write' => AllowMutableSession::class,
        ]);
        
        $this->config->extend('middleware.priority', [
            StartSession::class,
            AllowMutableSession::class,
            VerifyCsrfToken::class,
            HandleStatefulRequest::class,
            ShareSessionWithViews::class,
        ]);
        
        $this->config->extend('middleware.groups.stateful', [
            StartSession::class,
            VerifyCsrfToken::class,
            HandleStatefulRequest::class,
            ShareSessionWithViews::class,
            AddResponseAttributesToSession::class,
        ]);
        
        if ($this->container->has(GlobalViewContext::class)) {
            $this->config->extend('middleware.priority', [
                StartSession::class,
                AllowMutableSession::class,
                VerifyCsrfToken::class,
                HandleStatefulRequest::class,
            ]);
            $this->config->extend('middleware.groups.stateful', [
                ShareSessionWithViews::class,
            ]);
        }
    }
    
    private function bindSessionDriver()
    {
        $this->container->singleton(SessionDriver::class, function () {
            $driver = null;
            $driver_name = $this->config->get('session.driver', 'database');
            
            /** @var SessionConfig $session_config */
            $session_config = $this->container[SessionConfig::class];
            
            if ($driver_name === 'database') {
                global $wpdb;
                
                $table = $this->config->get('session.table');
                
                $driver = new DatabaseSessionDriver($wpdb, $table);
            }
            
            if ($driver_name === 'wp_object_cache') {
                $key = $this->config->get('session.wp_object_cache_group');
                if ( ! is_string($key)) {
                    throw new LogicException(
                        "You need to specify a value for [wp_object_cache_group] in your config/session.php file to use the wp_object_cache driver."
                    );
                }
                
                $driver = new WPObjectCacheDriver(
                    $this->config->get('session.wp_object_cache_group'),
                    $session_config->idleTimeoutInSec(),
                );
            }
            
            if ($this->app->isRunningUnitTest() && is_null($driver)) {
                $driver = new ArraySessionDriver();
            }
            
            if (is_null($driver)) {
                throw new LogicException("Invalid session driver configuration.");
            }
            
            if (filter_var($this->config['session.encrypt'], FILTER_VALIDATE_BOOLEAN)) {
                if ( ! $this->container->has(SessionEncryptor::class)) {
                    throw new LogicException(
                        sprintf(
                            "You need to bind an implementation of [%s] to use the encrypted session driver.",
                            SessionEncryptor::class
                        )
                    );
                }
                
                $driver = new EncryptedDriver($driver, $this->container[SessionEncryptor::class]);
            }
            
            return $driver;
        });
    }
    
    private function bindSessionManager()
    {
        $this->container->singleton(SessionManagerInterface::class, function () {
            return new SessionManager(
                $this->container[SessionConfig::class],
                $this->container[SessionDriver::class],
                new ClockUsingDateTimeImmutable(),
                new EventDispatcherUsingWPHooks(),
            );
        });
    }
    
    private function bindEvents()
    {
        if (in_array(AuthServiceProvider::class, $this->config->get('app.providers', []))) {
            return;
        }
        
        $this->config->extend('events.mapped', [
            'wp_login' => [UserLoggedIn::class],
            'wp_logout' => [UserLoggedOut::class],
        ]);
        
        $this->config->extend('events.listeners', [
            UserLoggedIn::class => [
                [InvalidateSession::class, 'handleLogin'],
            ],
            UserLoggedOut::class => [
                [InvalidateSession::class, 'handleLogout'],
            ],
        ]);
        
        $this->container->singleton(InvalidateSession::class, function () {
            return new InvalidateSession(
                $this->container[ResponseEmitter::class],
                $this->container[SessionManagerInterface::class],
            );
        });
    }
    
    private function bindSessionEventDispatcher()
    {
        $this->container->singleton(SessionEventDispatcher::class, function () {
            return new SessionEventDispatcherUsingBetterWPHooks(
                $this->container[Dispatcher::class]
            );
        });
    }
    
    private function bindMiddlewares()
    {
        $this->container->singleton(VerifyCsrfToken::class, function () {
            return new VerifyCsrfToken();
        });
        
        $this->container->singleton(HandleStatefulRequest::class, function () {
            return new HandleStatefulRequest(
                $this->container[SessionManagerInterface::class],
                $this->container[LoggerInterface::class],
            );
        });
        
        $this->container->singleton(AllowMutableSession::class, function () {
            return new AllowMutableSession($this->container[SessionManagerInterface::class]);
        });
        
        $this->container->singleton(StartSession::class, function () {
            /** @var SessionConfig $config */
            $config = $this->container[SessionConfig::class];
            
            return new StartSession(
                $config->cookiePath(),
                $this->container[SessionManagerInterface::class]
            );
        });
        
        $this->container->singleton(ShareSessionWithViews::class, function () {
            return new ShareSessionWithViews(
                $this->container[GlobalViewContext::class]
            );
        });
        
        $this->container->singleton(AddResponseAttributesToSession::class, function () {
            return new AddResponseAttributesToSession();
        });
    }
    
}