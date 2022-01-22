<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks;

use Snicco\Component\EventDispatcher\EventDispatcher;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;

final class SessionEventDispatcherUsingBetterWPHooks implements SessionEventDispatcher
{
    
    /**
     * @var EventDispatcher
     */
    private $dispatcher;
    
    public function __construct(EventDispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    /**
     * @param  array<object>  $events
     *
     * @return void
     */
    public function dispatchAll(array $events) :void
    {
        foreach ($events as $event) {
            $this->dispatcher->dispatch($event);
        }
    }
    
}