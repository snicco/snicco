<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\CustomizablePayload;

/**
 * If you dispatch an event as a string instead of using a dedicated class
 * the event will be transformed into a GenericEvent.
 * Assuming you would call $dispatcher->dispatch('foo_event', 'bar', ['baz', 'biz]'):
 * A listener would receive the three arguments in this order. ('bar', ['baz','biz'], 'foo_event').
 *
 * @note Events you dispatch as a string ARE NOT MUTABLE and thus can not be filtered.
 * They can only be used as an action.
 * @api
 */
final class GenericEvent implements Event, CustomizablePayload
{
    
    private array $arguments;
    
    public function __construct(array $arguments)
    {
        $this->arguments = $arguments;
    }
    
    public function payload() :array
    {
        return $this->arguments;
    }
    
}