<?php

declare(strict_types=1);

namespace Snicco\PimpleContainer;

use Closure;
use Pimple\Container;
use Snicco\Shared\ContainerAdapter;
use Pimple\Exception\FrozenServiceException;

final class PimpleContainerAdapter extends ContainerAdapter
{
    
    /**
     * @var Container
     */
    private $pimple;
    
    public function __construct(Container $container = null)
    {
        $this->pimple = $container ?? new Container();
    }
    
    public function factory(string $id, Closure $service) :void
    {
        try {
            $this->pimple[$id] = $this->pimple->factory($service);
        } catch (FrozenServiceException $e) {
            throw \Snicco\Shared\FrozenServiceException::from($e);
        }
    }
    
    public function singleton(string $id, Closure $service) :void
    {
        try {
            $this->pimple[$id] = $service;
        } catch (FrozenServiceException $e) {
            throw \Snicco\Shared\FrozenServiceException::from($e);
        }
    }
    
    public function get(string $id)
    {
        return $this->pimple[$id];
    }
    
    public function primitive(string $id, $value) :void
    {
        $this->pimple[$id] = $value;
    }
    
    public function offsetExists($offset) :bool
    {
        return $this->pimple->offsetExists($offset);
    }
    
    public function offsetUnset($offset)
    {
        $this->pimple->offsetUnset($offset);
    }
    
    public function has(string $id) :bool
    {
        return $this->pimple->offsetExists($id);
    }
    
}