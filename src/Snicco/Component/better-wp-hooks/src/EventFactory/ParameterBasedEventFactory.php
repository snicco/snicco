<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventFactory;

use Snicco\Component\BetterWPHooks\Exception\CantCreateMappedEvent;
use stdClass;
use Throwable;

/**
 * @api
 */
final class ParameterBasedEventFactory implements MappedEventFactory
{


    /**
     * @template-covariant  T as DispatchesConditionally
     * @psalm-param class-string<T> $event_class
     * @param array $wordpress_hook_arguments The arguments that were received from the firing
     *                                           WordPress hook.
     *
     * @return T
     * @throws CantCreateMappedEvent
     */
    public function make(string $event_class, array $wordpress_hook_arguments)
    {
        try {
            return new stdClass();
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