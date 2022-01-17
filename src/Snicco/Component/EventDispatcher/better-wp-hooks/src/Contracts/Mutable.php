<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

/**
 * Use this interface in your event class to make it the dispatched event object mutable.
 * If an event implements this interface all listeners will receive the same instance of the object
 * and can manipulate it. This is a more OOP and controlled way to model traditional WordPress
 * filters.
 *
 * @api
 */
interface Mutable
{
    
}