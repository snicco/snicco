<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use ArrayAccess;
use Closure;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainer;
use Psr\Container\NotFoundExceptionInterface;
use ReturnTypeWillChange;
use Snicco\Component\Kernel\Exception\FrozenService;
use Webmozart\Assert\Assert;

use function class_exists;
use function interface_exists;
use function is_scalar;

/**
 * The DependencyInjection(DI) container takes care of lazily constructing and loading services
 * for your application.
 * The framework itself DOES NOT require your implementation to be capable of auto-wiring.
 * However, you are free to use a container that supports auto-wiring in your application code.
 */
abstract class DIContainer implements ArrayAccess, PsrContainer
{

    /**
     * After the lock method is called state changing method on the container must throw an
     * exception.
     *
     * @interal
     * @psalm-internal Snicco\Component\Kernel
     *
     * @throws FrozenService
     */
    abstract public function lock(): void;

    /**
     * Register a binding with the container.
     * This will not be a singleton but a new object everytime it gets resolved.
     *
     * @template T
     *
     * @param string|class-string<T> $id
     * @param Closure():T $service
     *
     * @throws FrozenService When trying to overwrite an already resolved singleton.
     */
    abstract public function factory(string $id, Closure $service): void;

    /**
     * @template T
     *
     * @param string|class-string<T> $offset
     *
     * @psalm-return ($offset is class-string ? T : mixed)
     * @return T|mixed
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    #[ReturnTypeWillChange]
    final public function offsetGet($offset)
    {
        return $this->make((string)$offset);
    }

    /**
     * @param mixed $offset
     * @param Closure():object|object|scalar $value
     */
    final public function offsetSet($offset, $value): void
    {
        if ($value instanceof Closure) {
            /**
             * @var Closure():object $value
             */
            $this->singleton((string)$offset, $value);
            return;
        }

        if (is_object($value)) {
            $this->instance((string)$offset, $value);
            return;
        }

        $this->primitive((string)$offset, $value);
    }

    /**
     * @template T
     *
     * @param string|class-string<T> $id
     *
     * @psalm-return ($id is class-string ? T : mixed)
     * @return T|mixed
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     */
    final public function make(string $id)
    {
        /**
         * @psalm-suppress MixedAssignment
         */
        $res = $this->get($id);

        if (class_exists($id) || interface_exists($id)) {
            if (!$res instanceof $id) {
                throw new LogicException("Resolved value for class-string [$id] must be instance of [$id].");
            }
            /** @var T $res */
            return $res;
        }

        /**
         * @psalm-suppress MixedReturnStatement
         */
        return $res;
    }

    /**
     * Register a lazy service in the container. Once the service is resolved the stored closure
     * MUST be run and return the service defined in the closure. NOT the closure itself.
     *
     * @template T
     *
     * @param string|class-string<T> $id
     * @param Closure():T $service
     *
     * @throws FrozenService When trying to overwrite an already resolved singleton.
     */
    abstract public function singleton(string $id, Closure $service): void;

    /**
     * Store the passed service as is in the container.
     *
     * @param string $id
     * @param object $service
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
     * @param scalar|array<scalar> $value
     */
    final public function primitive(string $id, $value): void
    {
        if (!is_scalar($value)) {
            Assert::isArray($value, '$value must be a scalar or an array of scalars. Got [%s].');
            Assert::allScalar($value, '$value must be a scalar or an array of scalars. Got [%s].');
        }
        $this->singleton($id, fn() => $value);
    }

}