<?php

declare(strict_types=1);

namespace Snicco\Session;

use Snicco\Session\Contracts\SessionEventDispatcher;

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