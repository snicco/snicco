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
     * @param  string  $event_name
     *
     * @return Listener
     * @throws ListenerCreationException
     */
    public function create($listener, string $event_name) :Listener;
    
}