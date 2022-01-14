<?php

declare(strict_types=1);

namespace Snicco\Core;

use Closure;
use ArrayAccess;
use Snicco\Core\Exception\FrozenServiceException;
use Psr\Container\ContainerInterface as PsrContainer;

/**
 * The DependencyInjection(DI) container takes care of lazily constructing and loading services
 * for your application.
 * The framework itself DOES NOT require your implementation to be capable of auto-wiring.
 * However, you are free to use a container that supports auto-wiring in your application code.
 *
 * @api
 */
abstract class DIContainer implements ArrayAccess, PsrContainer
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
     * Once resolved the same instance will be returned.
     *
     * @param  string  $id
     * @param  Closure  $service  When trying to overwrite an already resolved singleton.
     *
     * @throws FrozenServiceException When trying to overwrite an already resolved singleton.
     */
    abstract public function singleton(string $id, Closure $service) :void;
    
    /**
     * Stores a primitive value in the container.
     *
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
    
    public function offsetSet($offset, $value) :void
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