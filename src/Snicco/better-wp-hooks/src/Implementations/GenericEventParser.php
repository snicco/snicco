<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Implementations;

use InvalidArgumentException;
use Snicco\EventDispatcher\Contracts\Event;
use Snicco\EventDispatcher\Contracts\EventParser;

/**
 * @internal
 */
final class GenericEventParser implements EventParser
{
    
    /**
     * @inheritdoc
     */
    public function transformToEvent($event, array $payload) :Event
    {
        if ($event instanceof Event) {
            return $event;
        }
        
        if (is_object($event)) {
            return new GenericEvent(get_class($event), [$event]);
        }
        
        if (is_string($event)) {
            return new GenericEvent($event, $payload);
        }
        
        throw new InvalidArgumentException(
            'A dispatched event has to be a string or an instance of ['.Event::class.'].'
        );
    }
    
}