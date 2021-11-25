<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\Exceptions\MappedEventCreationException;

/**
 * You can implement this interface and resolve mapped events using a DI container if for example
 * you need some configuration value. If you choose to do this use this feature sparingly.
 * Event class are meant to be data classes and should have limited behaviour. Under no
 * circumstances should you use an event class to provide dependencies to one of your attached
 * listeners. Use the ListenerFactory interface for this.
 *
 * @see ListenerFactory
 * @api
 */
interface MappedEventFactory
{
    
    /**
     * @param  string  $event_class
     * @param  array  $wordpress_hook_arguments
     * The arguments that were received from the firing WordPress hook.
     *
     * @return MappedAction|MappedFilter
     * @throws MappedEventCreationException
     */
    public function make(string $event_class, array $wordpress_hook_arguments) :Event;
    
}