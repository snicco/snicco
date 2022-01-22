<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher\Contracts;

use Snicco\Component\EventDispatcher\Exceptions\InvalidEventException;
use Snicco\Component\EventDispatcher\Implementations\GenericEventParser;

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
     * @throws InvalidEventException
     * @see GenericEventParser::transformToEvent()
     */
    public function transformToEvent($event, array $payload) :Event;
    
}