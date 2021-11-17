<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

interface DispatchesConditionally
{
    
    /**
     * @param  Event|mixed  $event
     *
     * @return bool
     */
    public function shouldDispatch($event) :bool;
    
}