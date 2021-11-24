<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Closure;
use Throwable;
use Snicco\EventDispatcher\Listener;
use Snicco\EventDispatcher\Contracts\ListenerFactory;
use Snicco\EventDispatcher\Exceptions\ListenerCreationException;

/**
 * @internal
 */
final class ParameterBasedListenerFactory implements ListenerFactory
{
    
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
        
        return new Listener(function (...$payload) use ($instance, $listener) {
            return $instance->{$listener[1]}(...$payload);
        }
        );
    }
    
}