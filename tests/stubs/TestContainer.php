<?php

declare(strict_types=1);

namespace Tests\stubs;

use Contracts\ContainerAdapter;

class TestContainer implements ContainerAdapter
{
    
    private array $bindings = [];
    
    public function make($abstract, array $parameters = [])
    {
        return $this->bindings[$abstract];
    }
    
    public function swapInstance($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function instance($abstract, $instance)
    {
        $this->bindings[$abstract] = $instance;
    }
    
    public function call($callable, array $parameters = [])
    {
        //
    }
    
    public function bind($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
        
    }
    
    public function singleton($abstract, $concrete)
    {
        $this->bindings[$abstract] = $concrete;
    }
    
    public function offsetExists($offset)
    {
        return isset($this->bindings[$offset]);
    }
    
    public function offsetGet($offset)
    {
        return $this->bindings[$offset];
    }
    
    public function offsetSet($offset, $value)
    {
        $this->bindings[$offset] = $value;
    }
    
    public function offsetUnset($offset)
    {
        unset($this->bindings[$offset]);
    }
    
    public function implementation()
    {
        return $this;
    }
    
}