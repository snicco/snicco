<?php

declare(strict_types=1);

namespace Snicco\Core\Application;

use Snicco\Core\Support\WP;
use Snicco\Core\Routing\Router;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Support\ReflectionDependencies;
use Snicco\Core\Contracts\UrlGeneratorInterface;
use Snicco\Core\ExceptionHandling\Exceptions\ConfigurationException;

class ApplicationServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindConfig();
        $this->bindAliases();
        $this->container->singleton(ReflectionDependencies::class, function () {
            return new ReflectionDependencies($this->container);
        });
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
        $app = $this->container->get(Application::class);
        
        $this->applicationAliases($app);
        $this->responseAliases($app);
        $this->routingAliases($app);
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
        $app->alias('url', UrlGeneratorInterface::class);
        $app->alias('routeUrl', UrlGeneratorInterface::class, 'toRoute');
        $app->alias('post', Router::class, 'post');
        $app->alias('get', Router::class, 'get');
        $app->alias('patch', Router::class, 'patch');
        $app->alias('put', Router::class, 'put');
        $app->alias('options', Router::class, 'options');
        $app->alias('delete', Router::class, 'delete');
        $app->alias('match', Router::class, 'match');
    }
    
}
