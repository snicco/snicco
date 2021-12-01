<?php

declare(strict_types=1);

namespace Snicco\Application;

use Snicco\Shared\ContainerAdapter;

trait HasContainer
{
    
    private ?ContainerAdapter $container_adapter;
    
    public function container() :?ContainerAdapter
    {
        return $this->container_adapter;
    }
    
    public function setContainer(ContainerAdapter $container_adapter) :void
    {
        $this->container_adapter = $container_adapter;
    }
    
    /**
     * Resolve a dependency from the IoC container.
     * Keys can be registered aliases.
     *
     * @param  string  $key
     *
     * @return mixed
     */
    public function resolve(string $key)
    {
        return $this->container_adapter[$key];
    }
    
    public function offsetExists($offset)
    {
        return $this->container_adapter->offsetExists($offset);
    }
    
    public function offsetGet($offset)
    {
        return $this->container_adapter->offsetGet($offset);
    }
    
    public function offsetSet($offset, $value)
    {
        $this->container_adapter->offsetSet($offset, $value);
    }
    
    public function offsetUnset($offset)
    {
        $this->container_adapter->offsetUnset($offset);
    }
    
}
