<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Exceptions;

use Closure;
use LogicException;

/**
 * @api
 */
final class UnremovableListenerException extends LogicException
{
    
    /**
     * @param  array|Closure  $listener
     * @param  string  $event_name
     *
     * @return static
     */
    public static function becauseTheDeveloperTriedToRemove($listener, string $event_name) :self
    {
        $identifier = $listener instanceof Closure
            ? ['Closure', spl_object_hash($listener)]
            : $listener;
        
        return new UnremovableListenerException(
            sprintf(
                "The listener [%s] is marked as unremovable for the event [%s].",
                implode(',', $identifier),
                $event_name
            )
        );
    }
    
}