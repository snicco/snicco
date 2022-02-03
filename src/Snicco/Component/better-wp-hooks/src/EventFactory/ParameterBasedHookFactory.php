<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPHooks\EventFactory;

use Snicco\Component\BetterWPHooks\EventMapping\MappedHook;
use Snicco\Component\BetterWPHooks\Exception\CantCreateMappedEvent;
use Throwable;

/**
 * @api
 */
final class ParameterBasedHookFactory implements MappedHookFactory
{

    public function make(string $event_class, array $wordpress_hook_arguments): MappedHook
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