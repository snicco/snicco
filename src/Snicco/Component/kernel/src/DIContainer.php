<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use ArrayAccess;
use Closure;
use InvalidArgumentException;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainer;
use Psr\Container\NotFoundExceptionInterface;
use ReturnTypeWillChange;
use Snicco\Component\Kernel\Exception\FrozenService;

use function class_exists;
use function gettype;
use function interface_exists;
use function is_array;
use function is_scalar;
use function sprintf;

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
     * @param string $id
     * @param Closure $service
     *
     * @throws FrozenService When trying to overwrite an already resolved singleton.
     */
    abstract public function factory(string $id, Closure $service): void;

    /**
     * @template T
     * @param string|class-string<T> $offset
     * @return T|mixed
     * @psalm-return ($offset is class-string ? T : mixed)
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
     * @template T
     * @param string|class-string<T> $id
     * @return T|mixed
     * @psalm-return ($id is class-string ? T : mixed)
     *
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedAssignment
     */
    final public function make(string $id)
    {
        $res = $this->get($id);

        if (class_exists($id) || interface_exists($id)) {
            if (!$res instanceof $id) {
                throw new LogicException("Resolved value for class-string [$id] must be instance of [$id].");
            }
            /** @var T $res */
            return $res;
        }

        return $res;
    }

    final public function offsetSet($offset, $value): void
    {
        if ($value instanceof Closure) {
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
     * @param scalar|array<scalar>|mixed $value
     */
    final public function primitive(string $id, $value): void
    {
        if (!is_scalar($value) && !is_array($value)) {
            throw new InvalidArgumentException(
                sprintf('$value must be Closure,object or scalar. Got [%s].', gettype($value))
            );
        }
        $this->singleton($id, fn() => $value);
    }

}