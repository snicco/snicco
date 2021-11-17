<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Throwable;
use RuntimeException;

final class ListenerCreationException extends RuntimeException
{
    
    public static function becauseTheListenerWasNotInstantiatable(array $listener, string $event_name, Throwable $previous) :MappedEventCreationException
    {
        $message =
            "The listener [$listener] could not be instantiated. Current event: [$event_name].";
        
        return new MappedEventCreationException($message, $previous->getCode(), $previous);
    }
    
}