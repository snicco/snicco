<?php

namespace Snicco\Traits;

use Contracts\ContainerAdapter;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Contracts\Container\Container as IlluminateContainerInterface;

/**
 * @property ContainerAdapter $container
 * @todo Move this to dedicated laravel-bridge package
 */
trait ReliesOnIlluminateContainer
{
    
    private function parseIlluminateContainer() :IlluminateContainerInterface
    {
        
        $concrete_container = $this->container->implementation();
        
        if ($concrete_container instanceof IlluminateContainerInterface) {
            
            return $concrete_container;
            
        }
        
        if ($this->container->offsetExists(IlluminateContainerInterface::class)) {
            
            return $this->container->make(IlluminateContainerInterface::class);
            
        }
        
        $this->container->instance(
            IlluminateContainerInterface::class,
            $c = new IlluminateContainer()
        );
        
        return $c;
        
    }
    
    private function setFacadeContainer(IlluminateContainerInterface $container)
    {
        
        if ( ! Facade::getFacadeApplication() instanceof IlluminateContainerInterface) {
            
            Facade::setFacadeApplication($container);
            
        }
        
    }
    
    // Unfortunately laravel uses the container like a global service locator in many places.
    // Not having one container instance globally available will lead to unexpected bugs.
    private function setGlobalContainerInstance(IlluminateContainerInterface $container)
    {
        
        Container::setInstance($container);
        
    }
    
}