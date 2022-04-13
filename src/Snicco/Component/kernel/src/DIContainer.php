<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel;

use ArrayAccess;
use Closure;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface as PsrContainer;
use Psr\Container\NotFoundExceptionInterface;
use ReturnTypeWillChange;
use Snicco\Component\Kernel\Exception\FrozenService;
use Webmozart\Assert\Assert;

/**
 * The DependencyInjection(DI) container takes care of lazily constructing and
 * loading services for your application. The snicco core itself DOES NOT
 * require your implementation to be capable of auto-wiring. However, you are
 * free to use a container that supports auto-wiring in your application code.
 */
abstract class DIContainer implements ArrayAccess, PsrContainer
{
    /**
     * After the lock method is called and method call that would change the
     * container must throw.
     *
     * {@see FrozenService}.
     *
     * @throws FrozenService
     *
     * @psalm-internal Snicco
     */
    abstract public function lock(): void;

    /**
     * Register a lazy callable in the container. This method MUST return a new
     * object every time the id is resolved from the container.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     * @param callable():T    $callable
     *
     * @throws FrozenService when trying to overwrite an already resolved shared service
     */
    abstract public function factory(string $id, callable $callable): void;

    /**
     * Register a lazy callable in the container that will only be called ONCE.
     * After resolving the service once the same object instance MUST be
     * returned every time its resolved.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     * @param callable():T    $callable
     *
     * @throws FrozenService when trying to overwrite an already resolved shared service
     */
    abstract public function shared(string $id, callable $callable): void;

    /**
     * Stores the passed instance as a shared service.
     *
     * @template T of object
     *
     * @param class-string<T> $id
     * @param T               $service
     *
     * @throws FrozenService when trying to overwrite an already resolved singleton
     */
    final public function instance(string $id, object $service): void
    {
        $this->shared($id, fn (): object => $service);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $offset
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @return T
     */
    #[ReturnTypeWillChange]
    final public function offsetGet($offset): object
    {
        Assert::stringNotEmpty($offset);

        return $this->make($offset);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $offset
     * @param callable():T|T  $value
     */
    final public function offsetSet($offset, $value): void
    {
        Assert::stringNotEmpty($offset);
        Assert::object($value);

        if ($value instanceof Closure) {
            /**
             * @var Closure():object $value
             */
            $this->shared($offset, $value);

            return;
        }

        $this->instance($offset, $value);
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-return T
     */
    final public function make(string $id): object
    {
        /**
         * @var mixed $res
         */
        $res = $this->get($id);

        Assert::isInstanceOf($res, $id);

        return $res;
    }
}
