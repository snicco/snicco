<?php

declare(strict_types=1);

namespace Snicco\Factories;

use Snicco\Contracts\ServiceProvider;

class FactoryServiceProvider extends ServiceProvider
{
    
    public function register() :void
    {
        $this->bindRouteActionFactory();
        $this->bindViewComposerFactory();
        $this->bindConditionFactory();
    }
    
    public function bootstrap() :void
    {
        //
    }
    
    private function bindRouteActionFactory() :void
    {
        $this->container->singleton(RouteActionFactory::class, function () {
            
            return new RouteActionFactory(
                $this->config['routing.controllers'] ?? [],
                $this->container
            );
            
        });
    }
    
    private function bindViewComposerFactory() :void
    {
        $this->container->singleton(ViewComposerFactory::class, function () {
            
            return new ViewComposerFactory(
                $this->config['view.composers'] ?? [],
                $this->container
            );
            
        });
    }
    
    private function bindConditionFactory() :void
    {
        $this->container->singleton(ConditionFactory::class, function () {
            
            return new ConditionFactory(
                
                $this->config->get('routing.conditions', []),
                $this->container,
            
            );
            
        });
    }
    
}