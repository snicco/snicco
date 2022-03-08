<?php

declare(strict_types=1);

namespace Snicco\Bridge\Pimple;

use Closure;
use Pimple\Container;
use Pimple\Exception\FrozenServiceException;
use ReturnTypeWillChange;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Exception\ContainerIsLocked;
use Snicco\Component\Kernel\Exception\FrozenService;

final class PimpleContainerAdapter extends DIContainer
{

    private Container $pimple;
    private bool $locked = false;

    public function __construct(Container $container = null)
    {
        $this->pimple = $container ?? new Container();
    }

    public function factory(string $id, callable $callable): void
    {
        if ($this->locked) {
            throw ContainerIsLocked::whileSettingId($id);
        }
        try {
            $this->pimple[$id] = $this->pimple->factory(Closure::fromCallable($callable));
        } catch (FrozenServiceException $e) {
            throw FrozenService::forId($id);
        }
    }

    public function get(string $id)
    {
        return $this->pimple[$id];
    }

    public function shared(string $id, callable $callable): void
    {
        if ($this->locked) {
            throw ContainerIsLocked::whileSettingId($id);
        }
        try {
            $this->pimple[$id] = Closure::fromCallable($callable);
        } catch (FrozenServiceException $e) {
            throw FrozenService::forId($id);
        }
    }

    public function offsetExists($offset): bool
    {
        return $this->pimple->offsetExists((string)$offset);
    }

    #[ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        if ($this->locked) {
            throw ContainerIsLocked::whileRemovingId((string)$offset);
        }
        $this->pimple->offsetUnset((string)$offset);
    }

    public function has(string $id): bool
    {
        return $this->pimple->offsetExists($id);
    }

    public function lock(): void
    {
        $this->locked = true;
    }

}