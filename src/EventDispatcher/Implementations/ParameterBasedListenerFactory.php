<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Closure;
use Throwable;
use Snicco\EventDispatcher\Listener;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Exceptions\ListenerCreationException;

/**
 * @api
 */
final class ParameterBasedListenerFactory implements ListenerFactory
{
    
    /**
     * @param  Closure|string[]  $listener
     * @param  string  $event_name
     *
     * @return Listener
     */
    public function create($listener, string $event_name) :Listener
    {
        if ($listener instanceof Closure) {
            return new Listener($listener);
        }
        try {
            $instance = new $listener[0];
        } catch (Throwable $e) {
            throw ListenerCreationException::becauseTheListenerWasNotInstantiatable(
                $listener,
                $event_name,
                $e
            );
        }
        
        return new Listener(fn(...$payload) => $instance->{$listener[1]}(...$payload));
    }
    
}