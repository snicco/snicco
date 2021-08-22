<?php

namespace Snicco\Bootstrap;

use Snicco\Application\Config;
use Snicco\Contracts\Bootstrapper;
use Snicco\Application\Application;
use Snicco\Http\HttpServiceProvider;
use Snicco\View\ViewServiceProvider;
use Snicco\Mail\MailServiceProvider;
use Snicco\Contracts\ServiceProvider;
use Snicco\Events\EventServiceProvider;
use Snicco\Routing\RoutingServiceProvider;
use Snicco\Factories\FactoryServiceProvider;
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
        $providers = collect($this->providers)->merge($user_providers);
        
        $providers->each(fn(string $provider) => $this->isValid($provider))
                  ->map(fn(string $provider) => $this->instantiate($provider, $app))
                  ->each(fn(ServiceProvider $provider) => $provider->register())
                  ->each(fn(ServiceProvider $provider) => $provider->bootstrap());
        
    }
    
    /**
     * @throws ConfigurationException
     */
    private function isValid(string $provider)
    {
        
        if ( ! is_subclass_of($provider, ServiceProvider::class)) {
            throw new ConfigurationException(
                'The following class does not implement '.
                ServiceProvider::class.': '.$provider
            );
        }
        
    }
    
    private function instantiate(string $provider, Application $app) :ServiceProvider
    {
        /** @var ServiceProvider $provider */
        $provider = new $provider($app->container(), $app[Config::class]);
        $provider->setApp($app);
        return $provider;
    }
    
}