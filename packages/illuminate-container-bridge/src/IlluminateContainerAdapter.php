<?php

declare(strict_types=1);

namespace Snicco\Illuminate;

use Closure;
use Snicco\Shared\ContainerAdapter;
use Illuminate\Container\Container;
use Snicco\Shared\FrozenServiceException;

final class IlluminateContainerAdapter extends ContainerAdapter
{
    
    /**
     * @var Container
     */
    private $illuminate_container;
    
    public function __construct(Container $container = null)
    {
        $this->illuminate_container = $container ?? new Container();
    }
    
    public function factory(string $id, Closure $service) :void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->bind($id, $service);
    }
    
    public function singleton(string $id, Closure $service) :void
    {
        $this->checkIfCanBeOverwritten($id);
        $this->illuminate_container->singleton($id, $service);
    }
    
    public function primitive(string $id, $value) :void
    {
        $this->illuminate_container->instance($id, $value);
    }
    
    public function get(string $id)
    {
        return $this->illuminate_container->get($id);
    }
    
    public function has(string $id)
    {
        $this->illuminate_container->has($id);
    }
    
    public function offsetExists($offset)
    {
        $this->illuminate_container->offsetExists($offset);
    }
    
    public function offsetUnset($offset)
    {
        $this->illuminate_container->offsetUnset($offset);
    }
    
    private function checkIfCanBeOverwritten(string $id)
    {
        if ( ! $this->illuminate_container->resolved($id)) {
            return;
        }
        
        if ( ! $this->illuminate_container->isShared($id)) {
            return;
        }
        
        throw new FrozenServiceException(
            sprintf('Singleton [%s] was already resolved and can not be overwritten.', $id)
        );
    }
    
}