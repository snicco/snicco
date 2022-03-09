<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Exception;

use InvalidArgumentException;

final class InvalidListener extends InvalidArgumentException
{
    public static function becauseListenerClassDoesntExist(string $listener): InvalidListener
    {
        return new InvalidListener("The listener [{$listener}] is not a valid class.");
    }

    public static function becauseListenerCantBeInvoked(string $listener): InvalidListener
    {
        return new InvalidListener(
            "The listener [{$listener}] does not define the __invoke() method."
        );
    }

    public static function becauseTheClosureDoesntHaveATypeHintedObject(): InvalidListener
    {
        return new InvalidListener(
            'A closure listener must have a type hinted object as the first parameter.'
        );
    }

    /**
     * @param array{0:class-string, 1:string } $listener
     */
    public static function becauseProvidedClassMethodDoesntExist(array $listener): InvalidListener
    {
        return new InvalidListener(
            "The listener class [{$listener[0]}] does not have a [{$listener[1]}] method."
        );
    }
}
