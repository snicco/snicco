<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

interface Event
{
    /**
     * The name that will be used to search for matching listeners.
     * Its recommend using the fully qualified class name.
     *
     * @see ClassAsName
     */
    public function name(): string;

    /**
     * The payload that all listeners for the event will receive.
     * It is recommended to use the dispatched class as the payload.
     * You can use the "ClassAsPayload" trait to achieve this behaviour.
     *
     * @return mixed
     *
     * @see ClassAsPayload
     */
    public function payload();
}
