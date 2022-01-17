<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\Dispatcher\EventDispatcher;

/**
 * @api
 */
interface ObjectCopier
{
    
    /**
     * Return an immutable copy of the posed event.
     *
     * @param  Event  $object
     *
     * @return Event
     * @see EventDispatcher::getPayloadForCurrentIteration()
     */
    public function copy(Event $object) :Event;
    
}