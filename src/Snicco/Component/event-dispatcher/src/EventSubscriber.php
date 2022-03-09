<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

interface EventSubscriber
{
    /**
     * @return array<string,string> ['event_name'=>'method_name']
     */
    public static function subscribedEvents(): array;
}
