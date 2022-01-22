<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Contracts;

use Closure;
use Snicco\Component\EventDispatcher\Listener;
use Snicco\Component\EventDispatcher\Exceptions\ListenerCreationException;

/**
 * @api
 */
interface ListenerFactory
{
    
    /**
     * Create a listener and inject constructor dependencies if the listener is a class.
     *
     * @param  Closure|array<string,string>  $listener
     * @param  string  $event_name
     *
     * @return Listener
     * @throws ListenerCreationException
     */
    public function create($listener, string $event_name) :Listener;
    
}