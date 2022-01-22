<?php

declare(strict_types=1);

namespace Snicco\SessionBundle\BetterWPHooks;

use Snicco\Component\EventDispatcher\Contracts\Dispatcher;
use Snicco\Component\Session\EventDispatcher\SessionEventDispatcher;

final class SessionEventDispatcherUsingBetterWPHooks implements SessionEventDispatcher
{
    
    /**
     * @var Dispatcher
     */
    private $dispatcher;
    
    public function __construct(Dispatcher $dispatcher)
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