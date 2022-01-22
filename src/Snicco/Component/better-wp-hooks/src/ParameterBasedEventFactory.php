<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks;

use Throwable;
use Snicco\Component\EventDispatcher\Event;

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
            throw CantCreateMappedEvent::becauseTheEventCouldNotBeConstructorWithArgs(
                $wordpress_hook_arguments,
                $event_class,
                $e
            );
        }
    }
    
}