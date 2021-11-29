<?php

declare(strict_types=1);

namespace Snicco\Illuminate;

use Snicco\Shared\ContainerAdapter;
use Illuminate\Container\Container;

final class IlluminateContainerAdapter implements ContainerAdapter
{
    
    private Container $container;
    
    public function __construct(Container $container = null)
    {
        $this->container = $container ?? new Container();
    }
    
    public function make($abstract, array $parameters = [])
    {
        return $this->container->make($abstract, $parameters);
    }
    
    public function swapInstance($abstract, $concrete)
    {
        return $this->instance($abstract, $concrete);
    }
    
    public function instance($abstract, $instance)
    {
        $this->container->instance($abstract, $instance);
        return $instance;
    }
    
    public function bind($abstract, $concrete)
    {
        $this->container->bind($abstract, $concrete);
    }
    
    public function singleton($abstract, $concrete)
    {
        $this->container->singleton($abstract, $concrete);
    }
    
    public function offsetExists($offset)
    {
        return $this->container->offsetExists($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->container->offsetGet($offset);
    }
    
    public function offsetSet($offset, $value)
    {
        $this->container->offsetSet($offset, $value);
    }
    
    public function offsetUnset($offset)
    {
        $this->container->offsetUnset($offset);
    }
    
}