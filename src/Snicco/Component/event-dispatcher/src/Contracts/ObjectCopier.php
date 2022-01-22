<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Contracts;

use Snicco\Component\EventDispatcher\Dispatcher\EventDispatcher;

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