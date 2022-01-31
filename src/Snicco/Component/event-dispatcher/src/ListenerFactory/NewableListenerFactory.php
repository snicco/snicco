<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\ListenerFactory;

use Throwable;
use Snicco\Component\EventDispatcher\Exception\CantCreateListener;

/**
 * @internal
 */
final class NewableListenerFactory implements ListenerFactory
{
    
    public function create(string $listener_class, string $event_name) :object
    {
        try {
            return new $listener_class();
        } catch (Throwable $e) {
            throw CantCreateListener::becauseTheListenerWasNotInstantiatable(
                $listener_class,
                $event_name,
                $e
            );
        }
    }
    
}