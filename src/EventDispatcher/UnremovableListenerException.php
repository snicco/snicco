<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use LogicException;

/**
 * @api
 */
final class UnremovableListenerException extends LogicException
{
    
    public static function becauseTheDeveloperTriedToRemove(string $listener_class, string $event_name) :self
    {
        return new UnremovableListenerException(
            "The listener [$listener_class] is marked as unremovable for the event [$event_name]."
        );
    }
    
}