<?php

declare(strict_types=1);

namespace Snicco\Component\Core;

use ArrayAccess;
use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainer;
use Psr\Container\NotFoundExceptionInterface;
use ReturnTypeWillChange;
use Snicco\Component\Core\Exception\FrozenService;

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
     * After the lock method is called state changing method on the container must throw an
     * exception.
     *
     * @throws FrozenService
     */
    abstract public function lock(): void;

    /**
     * Register a binding with the container.
     * This will not be a singleton but a new object everytime it gets resolved.
     *
     * @param string $id
     * @param Closure $service
     *
     * @throws FrozenService When trying to overwrite an already resolved singleton.
     */
    abstract public function factory(string $id, Closure $service): void;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    #[ReturnTypeWillChange]
    final public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    final public function offsetSet($offset, $value): void
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

    /**
     * Register a lazy service in the container. Once the service is resolved the stored closure
     * MUST be run and return the service defined in the closure. NOT the closure itself.
     *
     * @param string $id
     * @param Closure $service When trying to overwrite an already resolved singleton.
     *
     * @throws FrozenService When trying to overwrite an already resolved singleton.
     */
    abstract public function singleton(string $id, Closure $service): void;

    /**
     * Store the passed service as is in the container.
     *
     * @param string $id
     * @param object $service When trying to overwrite an already resolved singleton.
     *
     * @throws FrozenService When trying to overwrite an already resolved singleton.
     */
    final public function instance(string $id, object $service): void
    {
        $this->singleton($id, function () use ($service) {
            return $service;
        });
    }

    /**
     * Stores a primitive value in the container.
     *
     * @param string $id
     * @param array|string|int|float|bool $value
     */
    abstract public function primitive(string $id, $value): void;

}