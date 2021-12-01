<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

use Snicco\EventDispatcher\Listener;
use Snicco\EventDispatcher\ClassAsName;
use Snicco\EventDispatcher\ClassAsPayload;
use Snicco\EventDispatcher\Implementations\GenericEvent;

/**
 * The base interface all events have to implement.
 *
 * @api
 */
interface Event
{
    
    /**
     * The name that will be used to search for matching listeners.
     * Its recommend using the fully qualified class name.
     * You can use the trait "ClassAsName" to automatically achieve this behaviour and reduce duplication.
     *
     * @return string
     * @see ClassAsName
     * You can also return a custom name like "my_plugin_user_created" and clients would be able to
     * hook into an UserCreated event using add_filter('my_plugin_user_created');
     */
    public function getName() :string;
    
    /**
     * The payload that all listeners for the event will receive.
     * It is recommended to use the dispatched class as the payload.
     * You can use the "ClassAsPayload" trait to achieve this behaviour.
     *
     * @return mixed
     * @see GenericEvent
     * public function handle($user_name,$user_id, $event_name).
     * The event name is always passed as a last argument listeners dont have to receive nor use it.
     * @see Listener::call()
     * @see ClassAsPayload
     * You can also use a custom payload.
     * Assuming your payload is [$user_name,$user_id] a listener can handle the event like this:
     */
    public function getPayload();
    
}