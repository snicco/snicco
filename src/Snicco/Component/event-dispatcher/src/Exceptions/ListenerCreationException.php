<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Exceptions;

use Throwable;
use RuntimeException;

/**
 * @api
 */
final class ListenerCreationException extends RuntimeException
{
    
    /**
     * @param  string  $event_name
     * @param  Throwable  $previous
     * @param  array  $listener
     *
     * @return MappedEventCreationException
     */
    public static function becauseTheListenerWasNotInstantiatable(array $listener, string $event_name, Throwable $previous) :MappedEventCreationException
    {
        $message =
            "The listener [{$listener[0]}, {$listener[1]}] could not be instantiated. Current event: [$event_name].\nCaused by: [{$previous->getMessage()}]";
        
        return new MappedEventCreationException($message, $previous->getCode(), $previous);
    }
    
}