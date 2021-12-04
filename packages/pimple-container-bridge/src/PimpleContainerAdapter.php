<?php

declare(strict_types=1);

namespace Snicco\PimpleContainer;

use Closure;
use Pimple\Container;
use Snicco\Shared\ContainerAdapter;

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
    
    public function factory(string $abstract, Closure $concrete) :void
    {
        $this->pimple[$abstract] = $this->pimple->factory($concrete);
    }
    
    public function singleton(string $abstract, Closure $concrete) :void
    {
        $this->pimple[$abstract] = $concrete;
    }
    
    public function get(string $id)
    {
        return $this->pimple[$id];
    }
    
    public function primitive(string $abstract, $value) :void
    {
        $this->pimple[$abstract] = $value;
    }
    
    public function offsetExists($offset)
    {
        return $this->pimple->offsetExists($offset);
    }
    
    public function offsetUnset($offset)
    {
        $this->pimple->offsetUnset($offset);
    }
    
    public function has(string $id)
    {
        return $this->pimple->offsetExists($id);
    }
    
}