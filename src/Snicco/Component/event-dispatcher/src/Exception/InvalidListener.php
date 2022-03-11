<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Exception;

use InvalidArgumentException;

final class InvalidListener extends InvalidArgumentException
{
    public static function becauseListenerClassDoesntExist(string $listener): InvalidListener
    {
        return new InvalidListener(sprintf('The listener [%s] is not a valid class.', $listener));
    }

    public static function becauseListenerCantBeInvoked(string $listener): InvalidListener
    {
        return new InvalidListener(sprintf('The listener [%s] does not define the __invoke() method.', $listener));
    }

    public static function becauseTheClosureDoesntHaveATypeHintedObject(): InvalidListener
    {
        return new InvalidListener('A closure listener must have a type hinted object as the first parameter.');
    }

    /**
     * @param array{0:class-string, 1:string } $listener
     */
    public static function becauseProvidedClassMethodDoesntExist(array $listener): InvalidListener
    {
        return new InvalidListener(sprintf(
            'The listener class [%s] does not have a [%s] method.',
            $listener[0],
            $listener[1]
        ));
    }
}
