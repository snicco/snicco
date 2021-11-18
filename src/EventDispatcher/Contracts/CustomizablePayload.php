<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * @api
 * Add this interface to your event class to customize the payload that listeners will receive
 * instead of the full event object.
 */
interface CustomizablePayload
{
    
    public function payload() :array;
    
}