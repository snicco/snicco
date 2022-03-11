<?php

declare(strict_types=1);

namespace Snicco\Bridge\IlluminateContainer;

use Closure;
use Illuminate\Container\Container;
use ReturnTypeWillChange;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Exception\ContainerIsLocked;
use Snicco\Component\Kernel\Exception\FrozenService;

use function sprintf;

final class IlluminateContainerAdapter extends DIContainer
{
    private Container $illuminate_container;

    private bool $locked = false;

    public function __construct(Container $container = null)
    {
        $this->illuminate_container = $container ?? new Container();
    }

    public function factory(string $id, callable $callable): void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->bind($id, Closure::fromCallable($callable));
    }

    public function shared(string $id, callable $callable): void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->singleton($id, Closure::fromCallable($callable));
    }

    public function get(string $id)
    {
        return $this->illuminate_container->get($id);
    }

    public function has(string $id): bool
    {
        return $this->illuminate_container->has($id);
    }

    public function offsetExists($offset): bool
    {
        return $this->illuminate_container->offsetExists((string) $offset);
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        if ($this->locked) {
            throw ContainerIsLocked::whileRemovingId((string) $offset);
        }
        $this->illuminate_container->offsetUnset((string) $offset);
    }

    public function lock(): void
    {
        $this->locked = true;
    }

    private function checkIfCanBeOverwritten(string $id): void
    {
        if ($this->locked) {
            throw ContainerIsLocked::whileSettingId($id);
        }

        if (! $this->illuminate_container->resolved($id)) {
            return;
        }

        if (! $this->illuminate_container->isShared($id)) {
            return;
        }

        throw new FrozenService(sprintf('Singleton [%s] was already resolved and can not be overwritten.', $id));
    }
}
