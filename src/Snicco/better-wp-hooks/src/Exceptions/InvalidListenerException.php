<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Exceptions;

use InvalidArgumentException;

/**
 * @api
 */
final class InvalidListenerException extends InvalidArgumentException
{
    
    public static function becauseTheListenerIsNotAValidClass(string $listener) :InvalidListenerException
    {
        return new InvalidListenerException("The listener [$listener] is not a valid class.");
    }
    
    public static function becauseTheListenerHasNoValidMethod(string $listener) :InvalidListenerException
    {
        return new InvalidListenerException(
            "The listener [$listener] does not have a handle method and is not invokable with __invoke()."
        );
    }
    
    public static function becauseTheClosureDoesntHaveATypehintedEvent() :InvalidListenerException
    {
        return new InvalidListenerException(
            'The closure listener must have a type hinted event as the first parameter.'
        );
    }
    
}