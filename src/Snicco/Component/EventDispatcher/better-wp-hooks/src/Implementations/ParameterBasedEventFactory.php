<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Throwable;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\MappedEventFactory;
use Snicco\EventDispatcher\Exceptions\MappedEventCreationException;

/**
 * @internal
 */
final class ParameterBasedEventFactory implements MappedEventFactory
{
    
    public function make(string $event_class, array $wordpress_hook_arguments) :Event
    {
        try {
            return new $event_class(...$wordpress_hook_arguments);
        } catch (Throwable $e) {
            throw MappedEventCreationException::becauseTheEventCouldNotBeConstructorWithArgs(
                $wordpress_hook_arguments,
                $event_class,
                $e
            );
        }
    }
    
}