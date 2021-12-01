<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * Use this interface on your event class to control more gradually when it should be dispatched.
 * This may be useful if you dispatch the same event in multiple places to avoid duplication.
 *
 * @api
 */
interface DispatchesConditionally
{
    
    /**
     * If false is returned no listeners will handle the event.
     *
     * @return bool
     */
    public function shouldDispatch() :bool;
    
}