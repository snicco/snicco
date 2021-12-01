<?php

declare(strict_types=1);

namespace Snicco\Application;

use Snicco\Support\WP;
use Snicco\Routing\Router;
use Snicco\View\ViewEngine;
use Snicco\Http\MethodField;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Contracts\Redirector;
use Snicco\View\GlobalViewContext;
use Snicco\Contracts\ServiceProvider;
use Snicco\View\ViewComposerCollection;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class ApplicationServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindAliases();
    }
    
    public function bootstrap() :void
    {
        if ( ! $this->validAppKey()) {
            $info = Application::class.'::generateKey';
            throw new ConfigurationException(
                "Your app.key config value is either missing or too insecure. Please generate a new one using $info()"
            );
        }
    }
    
    private function bindConfig()
    {
        $this->config->extend('app.base_path', $this->app->basePath());
        $this->config->extend('app.package_root', dirname(__FILE__, 3));
        $this->config->extend(
            'app.storage_dir',
            $this->app->basePath().DIRECTORY_SEPARATOR.'storage'
        );
        $this->config->extendIfEmpty('app.url', fn() => WP::siteUrl());
        $this->config->extend('app.dist', DIRECTORY_SEPARATOR.'dist');
        $this->config->extend('app.exception_handling', true);
        $this->config->extend('app.debug', true);
    }
    
    private function bindAliases()
    {
        $app = $this->container->make(Application::class);
        
        $this->applicationAliases($app);
        $this->responseAliases($app);
        $this->routingAliases($app);
        $this->viewAliases($app);
        $this->bindRequestAlias($app);
    }
    
    private function applicationAliases(Application $app)
    {
        $app->alias('app', Application::class);
    }
    
    private function responseAliases(Application $app)
    {
        $app->alias('response', ResponseFactory::class);
        $app->alias('redirect', function (?string $path = null, int $status = 302) use ($app) {
            /** @var Redirector $redirector */
            $redirector = $app->resolve(Redirector::class);
            
            if ($path) {
                return $redirector->to($path, $status);
            }
            
            return $redirector;
        });
    }
    
    private function routingAliases(Application $app)
    {
        $app->alias('route', Router::class);
        $app->alias('url', UrlGenerator::class);
        $app->alias('routeUrl', UrlGenerator::class, 'toRoute');
        $app->alias('post', Router::class, 'post');
        $app->alias('get', Router::class, 'get');
        $app->alias('patch', Router::class, 'patch');
        $app->alias('put', Router::class, 'put');
        $app->alias('options', Router::class, 'options');
        $app->alias('delete', Router::class, 'delete');
        $app->alias('match', Router::class, 'match');
    }
    
    private function viewAliases(Application $app)
    {
        $app->alias('globals', function () use ($app) {
            /** @var GlobalViewContext $globals */
            $globals = $app->resolve(GlobalViewContext::class);
            
            $args = func_get_args();
            if (empty($args) || (is_null($args[0] && is_null($args[1])))) {
                return $globals;
            }
            
            $globals->add(...array_values(func_get_args()));
            
            return $globals;
        });
        $app->alias('addComposer', function () use ($app) {
            $composer_collection = $app->resolve(ViewComposerCollection::class);
            
            $args = func_get_args();
            
            $composer_collection->addComposer(...$args);
        });
        $app->alias('view', function () use ($app) {
            /** @var ViewEngine $view_service */
            $view_service = $app->container()->make(ViewEngine::class);
            
            return call_user_func_array([$view_service, 'make'], func_get_args());
        });
        $app->alias('render', function () use ($app) {
            /** @var ViewEngine $view_service */
            $view_service = $app->container()->make(ViewEngine::class);
            
            $view_as_string = call_user_func_array([$view_service, 'render',], func_get_args());
            
            echo $view_as_string;
        });
        $app->alias('methodField', MethodField::class, 'html');
    }
    
    private function bindRequestAlias(Application $app)
    {
        $app->alias('request', function () use ($app) {
            return $app->resolve(Request::class);
        });
    }
    
}
