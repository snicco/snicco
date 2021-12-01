<?php

declare(strict_types=1);

namespace Snicco\Factories;

use Snicco\Shared\ContainerAdapter;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Support\ReflectionDependencies;

class MiddlewareFactory
{
    
    private ContainerAdapter $container;
    
    public function __construct(ContainerAdapter $container)
    {
        $this->container = $container;
    }
    
    public function create(string $middleware_class, array $route_arguments) :MiddlewareInterface
    {
        if (isset($this->container[$middleware_class])) {
            return $this->container->make(
                $middleware_class,
                $route_arguments
            );
        }
        
        if (empty($route_arguments)) {
            return $this->container->make($middleware_class);
        }
        
        $constructor_args = (new ReflectionDependencies($this->container))
            ->build($middleware_class, $route_arguments);
        
        return new $middleware_class(...array_values($constructor_args));
    }
    
}