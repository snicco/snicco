<?php

declare(strict_types=1);

namespace Snicco\Bridge\Pimple;

use Closure;
use Pimple\Container;
use Snicco\Component\Core\DIContainer;
use Pimple\Exception\FrozenServiceException;
use Snicco\Component\Core\Exception\FrozenService;
use Snicco\Component\Core\Exception\ContainerIsLocked;

final class PimpleContainerAdapter extends DIContainer
{
    
    private Container $pimple;
    private bool      $locked = false;
    
    public function __construct(Container $container = null)
    {
        $this->pimple = $container ?? new Container();
    }
    
    public function factory(string $id, Closure $service) :void
    {
        if (true === $this->locked) {
            throw ContainerIsLocked::whileSettingId($id);
        }
        try {
            $this->pimple[$id] = $this->pimple->factory($service);
        } catch (FrozenServiceException $e) {
            throw FrozenService::forId($id);
        }
    }
    
    public function singleton(string $id, Closure $service) :void
    {
        if (true === $this->locked) {
            throw ContainerIsLocked::whileSettingId($id);
        }
        try {
            $this->pimple[$id] = $service;
        } catch (FrozenServiceException $e) {
            throw FrozenService::forId($id);
        }
    }
    
    public function get(string $id)
    {
        return $this->pimple[$id];
    }
    
    public function primitive(string $id, $value) :void
    {
        $this->singleton($id, fn() => $value);
    }
    
    public function offsetExists($offset) :bool
    {
        return $this->pimple->offsetExists($offset);
    }
    
    public function offsetUnset($offset)
    {
        if (true === $this->locked) {
            throw ContainerIsLocked::whileRemovingId($offset);
        }
        $this->pimple->offsetUnset($offset);
    }
    
    public function has(string $id) :bool
    {
        return $this->pimple->offsetExists($id);
    }
    
    public function lock() :void
    {
        $this->locked = true;
    }
    
}