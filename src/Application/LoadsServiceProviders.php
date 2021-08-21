<?php

declare(strict_types=1);

namespace Snicco\Application;

use Snicco\Contracts\ServiceProvider;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

trait LoadsServiceProviders
{
    
    public function loadServiceProviders() :void
    {
        
        $user_providers = $this->config->get('app.providers', []);
        
        $providers = collect(self::CORE_SERVICE_PROVIDERS)->merge($user_providers);
        
        $providers->each(fn(string $provider) => $this->isValid($provider))
                  ->map(fn(string $provider) => $this->instantiate($provider))
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
    
    private function instantiate(string $provider) :ServiceProvider
    {
        /** @var ServiceProvider $provider */
        $provider = new $provider($this->container(), $this->config);
        $provider->setApp($this);
        
        return $provider;
    }
    
}
