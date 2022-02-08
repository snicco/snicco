<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\ListenerFactory;

use Snicco\Component\EventDispatcher\Exception\CantCreateListener;
use Throwable;

final class NewableListenerFactory implements ListenerFactory
{

    /**
     * @psalm-suppress MixedMethodCall
     */
    public function create(string $listener_class, string $event_name): object
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