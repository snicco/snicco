<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventFactory;

use Snicco\Component\BetterWPHooks\Exception\CantCreateMappedEvent;
use Snicco\Component\EventDispatcher\Event;

/**
 * The MappedEventFactory is responsible for transforming arbitrary primitive values into
 * an {@see Event} object.
 *
 * @api
 * @see ParameterBasedEventFactory
 */
interface MappedEventFactory
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
    public function make(string $event_class, array $wordpress_hook_arguments);

}