<?php

namespace Snicco\Bootstrap;

use Snicco\Application\Config;
use Snicco\Contracts\Bootstrapper;
use Snicco\Application\Application;
use Snicco\Http\HttpServiceProvider;
use Snicco\Contracts\ServiceProvider;
use Snicco\MailBundle\MailServiceProvider;
use Snicco\ViewBundle\ViewServiceProvider;
use Snicco\Routing\RoutingServiceProvider;
use Snicco\Factories\FactoryServiceProvider;
use Snicco\EventDispatcher\EventServiceProvider;
use Snicco\Middleware\MiddlewareServiceProvider;
use Snicco\Application\ApplicationServiceProvider;
use Snicco\ExceptionHandling\ExceptionServiceProvider;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

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
        ViewServiceProvider::class,
        MailServiceProvider::class,
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