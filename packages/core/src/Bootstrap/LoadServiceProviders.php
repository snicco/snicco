<?php

namespace Snicco\Core\Bootstrap;

use Snicco\Core\Application\Config;
use Snicco\Core\Contracts\Bootstrapper;
use Snicco\Core\Application\Application;
use Snicco\Core\Http\HttpServiceProvider;
use Snicco\Core\Contracts\ServiceProvider;
use Snicco\Core\Routing\RoutingServiceProvider;
use Snicco\Core\Factories\FactoryServiceProvider;
use Snicco\Core\EventDispatcher\EventServiceProvider;
use Snicco\Core\Middleware\MiddlewareServiceProvider;
use Snicco\Core\Application\ApplicationServiceProvider;
use Snicco\Core\ExceptionHandling\ExceptionServiceProvider;
use Snicco\Core\ExceptionHandling\Exceptions\ConfigurationException;

class LoadServiceProviders implements Bootstrapper
{
    
    private array $providers = [
        ApplicationServiceProvider::class,
        ExceptionServiceProvider::class,
        EventServiceProvider::class,
        FactoryServiceProvider::class,
        RoutingServiceProvider::class,
        HttpServiceProvider::class,
        MiddlewareServiceProvider::class,
    ];
    
    public function bootstrap(Application $app) :void
    {
        $user_providers = $app->config('app.providers', []);
        $providers = array_merge($this->providers, $user_providers);
        
        array_walk($providers, function ($provider) {
            $this->isValid($provider);
        });
        
        $providers = array_map(function ($provider) use ($app) {
            return $this->instantiate($provider, $app);
        }, $providers);
        
        array_walk($providers, function (ServiceProvider $provider) {
            $provider->register();
        }, $providers);
        
        array_walk($providers, function (ServiceProvider $provider) {
            $provider->bootstrap();
        }, $providers);
    }
    
    /**
     * @throws ConfigurationException
     */
    private function isValid($provider)
    {
        if ( ! is_subclass_of($provider, ServiceProvider::class)) {
            throw new ConfigurationException(
                'The following class does not implement '.
                ServiceProvider::class.': '.$provider
            );
        }
    }
    
    private function instantiate($provider, Application $app) :ServiceProvider
    {
        // We also allow already instantiated service providers.
        // This is useful for testing where we might want to push a
        // custom provider to customize the config at runtime.
        if ($provider instanceof ServiceProvider) {
            return $provider;
        }
        
        /** @var ServiceProvider $provider */
        $provider = new $provider($app->container(), $app[Config::class]);
        $provider->setApp($app);
        return $provider;
    }
    
}