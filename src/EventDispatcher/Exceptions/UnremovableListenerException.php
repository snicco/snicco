<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Exceptions;

use LogicException;

/**
 * @api
 */
final class UnremovableListenerException extends LogicException
{
    
    /**
     * @param  string  $listener_class
     * @param  string  $event_name
     *
     * @return static
     */
    public static function becauseTheDeveloperTriedToRemove(string $listener_class, string $event_name) :self
    {
        return new UnremovableListenerException(
            "The listener [$listener_class] is marked as unremovable for the event [$event_name]."
        );
    }
    
}