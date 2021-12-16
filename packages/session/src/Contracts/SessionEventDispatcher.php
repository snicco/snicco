<?php

declare(strict_types=1);

namespace Snicco\Session\Contracts;

/**
 * @api
 */
interface SessionEventDispatcher
{
    
    /**
     * @param  array<object>  $events
     */
    public function dispatchAll(array $events) :void;
    
}