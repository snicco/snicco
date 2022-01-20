<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionWP;

use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;

use function do_action;

/**
 * @interal
 */
final class EventDispatcherUsingWPHooks implements SessionEventDispatcher
{
    
    public function dispatchAll(array $events) :void
    {
        foreach ($events as $event) {
            do_action(get_class($event), $event);
        }
    }
    
}