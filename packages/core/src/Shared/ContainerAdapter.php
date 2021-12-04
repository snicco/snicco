<?php

declare(strict_types=1);

namespace Snicco\Shared;

use Closure;
use ArrayAccess;
use Psr\Container\ContainerInterface;

abstract class ContainerAdapter implements ArrayAccess, ContainerInterface
{
    
    /**
     * Register a binding with the container.
     * This will not be a singleton but a new object everytime it gets resolved.
     *
     * @param  string  $abstract
     * @param  Closure  $concrete
     */
    abstract public function factory(string $abstract, Closure $concrete) :void;
    
    /**
     * Register a shared binding in the container.
     * This object will be a singleton always
     *
     * @param  string  $abstract
     * @param  Closure  $concrete
     */
    abstract public function singleton(string $abstract, Closure $concrete) :void;
    
    /**
     * @param  string  $abstract
     * @param  array|string|int|float|bool  $value
     */
    abstract public function primitive(string $abstract, $value) :void;
    
    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $abstract
     * @param  object  $instance
     */
    public function instance(string $abstract, object $instance) :void
    {
        $this->singleton($abstract, function () use ($instance) {
            return $instance;
        });
    }
    
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }
    
    public function offsetSet($offset, $value)
    {
        if ($value instanceof Closure) {
            $this->singleton($offset, $value);
            return;
        }
        
        if (is_object($value)) {
            $this->instance($offset, $value);
            return;
        }
        
        $this->primitive($offset, $value);
    }
    
}