<?php

declare(strict_types=1);

namespace Snicco\Bridge\IlluminateContainer;

use Closure;
use Illuminate\Container\Container;
use ReturnTypeWillChange;
use Snicco\Component\Core\DIContainer;
use Snicco\Component\Core\Exception\ContainerIsLocked;
use Snicco\Component\Core\Exception\FrozenService;

use function sprintf;

final class IlluminateContainerAdapter extends DIContainer
{

    private Container $illuminate_container;
    private bool $locked = false;

    public function __construct(Container $container = null)
    {
        $this->illuminate_container = $container ?? new Container();
    }

    public function factory(string $id, Closure $service): void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->bind($id, $service);
    }

    private function checkIfCanBeOverwritten(string $id)
    {
        if (true === $this->locked) {
            throw ContainerIsLocked::whileSettingId($id);
        }

        if (!$this->illuminate_container->resolved($id)) {
            return;
        }

        if (!$this->illuminate_container->isShared($id)) {
            return;
        }

        throw new FrozenService(
            sprintf('Singleton [%s] was already resolved and can not be overwritten.', $id)
        );
    }

    public function singleton(string $id, Closure $service): void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->singleton($id, $service);
    }

    public function primitive(string $id, $value): void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->singleton($id, fn() => $value);
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
        return $this->illuminate_container->offsetExists($offset);
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if (true === $this->locked) {
            throw ContainerIsLocked::whileRemovingId($offset);
        }
        $this->illuminate_container->offsetUnset($offset);
    }

    public function lock(): void
    {
        $this->locked = true;
    }

}