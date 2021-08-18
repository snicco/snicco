<?php

declare(strict_types=1);

namespace Snicco\Http;

use Contracts\ContainerAdapter;

class MiddlewareResolver
{
    
    private ContainerAdapter $container;
    
    public function __construct(ContainerAdapter $container)
    {
        
        $this->container = $container;
    }
    
    public function resolveFor(array $callable) :array
    {
        
        [$class, $method] = $callable;
        
        if ( ! method_exists($class, 'getMiddleware')) {
            return [];
        }
        
        /** @var Controller $controller_instance */
        $controller_instance = $this->container->make($class);
        
        // Don't resolve this controller again when we hit the route.
        $this->container->instance($class, $controller_instance);
        
        return $controller_instance->getMiddleware($method);
        
    }
    
}