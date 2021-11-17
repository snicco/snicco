<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Closure;
use Snicco\Shared\ContainerAdapter;
use Snicco\EventDispatcher\Contracts\ListenerFactory;

/**
 * @api
 */
final class DependencyInversionListenerFactory implements ListenerFactory
{
    
    private ContainerAdapter $container;
    
    public function __construct(ContainerAdapter $container)
    {
        $this->container = $container;
    }
    
    /**
     * @internal
     *
     * @param  Closure|string[]  $listener
     *
     * @return Listener
     */
    public function create($listener) :Listener
    {
        if ($listener instanceof Closure) {
            return new Listener($listener);
        }
        
        $instance = $this->container->make($listener[0]);
        
        return new Listener(fn(...$payload) => $instance->{$listener[1]}(...$payload));
    }
    
}