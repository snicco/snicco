<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Closure;
use Snicco\EventDispatcher\Listener;

/**
 * @api
 */
interface ListenerFactory
{
    
    /**
     * Create a listener and inject constructor dependencies if the listener is a class.
     *
     * @param  Closure|array<string,string>  $listener
     *
     * @return Listener
     */
    public function create($listener) :Listener;
    
}