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
    }
    
    public function bootstrap() :void
    {
        $this->bindConditionFactory();
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
                $this->container,
                $this->config['view.composers'] ?? []
            );
        });
    }
    
    private function bindConditionFactory() :void
    {
        $this->container->singleton(RouteConditionFactory::class, function () {
            return new RouteConditionFactory(
                
                $this->config->get('routing.conditions', []),
                $this->container,
            
            );
        });
    }
    
}