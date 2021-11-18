<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Closure;
use Snicco\EventDispatcher\Listener;
use Snicco\EventDispatcher\Exceptions\ListenerCreationException;

/**
 * @api
 */
interface ListenerFactory
{
    
    /**
     * Create a listener and inject constructor dependencies if the listener is a class.
     *
     * @param  Closure|array<string,string>  $listener
     * @param  Event  $event  The event that is being dispatched.
     *
     * @return Listener
     * @throws ListenerCreationException
     */
    public function create($listener, Event $event) :Listener;
    
}