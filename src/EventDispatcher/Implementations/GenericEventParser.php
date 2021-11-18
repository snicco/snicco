<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\EventParser;

/**
 * @internal
 */
final class GenericEventParser implements EventParser
{
    
    public function transformToEvent($event, array $payload) :Event
    {
        if ($event instanceof Event) {
            return $event;
        }
        
        return new GenericEvent($event, $payload);
    }
    
}