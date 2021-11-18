<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\Exceptions\MappedEventCreationException;

/**
 * @api
 * You can implement this interface and resolve mapped events using a DI container if for example
 *     you need some configuration value. If you choose to do this use this feature sparingly.
 *     Event class are ment to be data classes and should have limited behaviour. Under no
 *     circumstances should you use an event class to provide dependencies to one of your attached
 *     listeners. Use the ListenerFactory interface for this. @see ListenerFactory
 */
interface MappedEventFactory
{
    
    /**
     * @param  string  $event_class
     * @param  array  $wordpress_hook_arguments
     *
     * @return MappedAction|MappedFilter
     * @throws MappedEventCreationException
     */
    public function make(string $event_class, array $wordpress_hook_arguments) :Event;
    
}