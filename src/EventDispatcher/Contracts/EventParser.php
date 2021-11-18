<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\Implementations\GenericEventParser;

/**
 * @api
 */
interface EventParser
{
    
    /**
     * @param  string|Event  $event
     * @param  array  $payload
     *
     * @return Event
     * @see GenericEventParser::transformToEvent()
     */
    public function transformToEvent($event, array $payload) :Event;
    
}