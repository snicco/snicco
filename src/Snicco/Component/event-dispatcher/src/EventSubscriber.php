<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

/**
 * @api
 */
interface EventSubscriber
{

    /**
     * @return array<string,string> ['event_name'=>'method_name']
     */
    public static function subscribedEvents(): array;

}