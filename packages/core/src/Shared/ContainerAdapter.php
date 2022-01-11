<?php

declare(strict_types=1);

namespace Snicco\Core\Shared;

use Closure;
use ArrayAccess;
use Psr\Container\ContainerInterface;

/**
 * @todo classes outside the bootstrap process should depend on the psr3 interface not the full
 *     adapter.
 */
abstract class ContainerAdapter implements ArrayAccess, ContainerInterface
{
    
    /**
     * Register a binding with the container.
     * This will not be a singleton but a new object everytime it gets resolved.
     *
     * @param  string  $id
     * @param  Closure  $service
     *
     * @throws FrozenServiceException When trying to overwrite an already resolved singleton.
     */
    abstract public function factory(string $id, Closure $service) :void;
    
    /**
     * Register a shared binding in the container.
     * This object will be a singleton always
     *
     * @param  string  $id
     * @param  Closure  $service  When trying to overwrite an already resolved singleton.
     *
     * @throws FrozenServiceException When trying to overwrite an already resolved singleton.
     */
    abstract public function singleton(string $id, Closure $service) :void;
    
    /**
     * @param  string  $id
     * @param  array|string|int|float|bool  $value
     */
    abstract public function primitive(string $id, $value) :void;
    
    /**
     * Register an existing instance as shared in the container.
     *
     * @param  string  $id
     * @param  object  $service  When trying to overwrite an already resolved singleton.
     *
     * @throws FrozenServiceException When trying to overwrite an already resolved singleton.
     */
    public function instance(string $id, object $service) :void
    {
        $this->singleton($id, function () use ($service) {
            return $service;
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