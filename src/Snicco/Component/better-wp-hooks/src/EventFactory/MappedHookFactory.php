<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventFactory;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\BetterWPHooks\Exception\CantCreateMappedEvent;
use Snicco\Component\EventDispatcher\Event;

/**
 * The MappedEventFactory is responsible for transforming arbitrary primitive values into
 * an {@see Event} object.
 *
 * @see ParameterBasedHookFactory
 */
interface MappedHookFactory
{
    /**
     * @template  T of MappedHook
     *
     * @param class-string<T> $event_class
     * @param array           $wordpress_hook_arguments the arguments that were received from the firing
     *                                                  WordPress hook
     *
     * @throws CantCreateMappedEvent
     *
     * @return T
     */
    public function make(string $event_class, array $wordpress_hook_arguments): MappedHook;
}
