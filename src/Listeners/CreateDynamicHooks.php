<?php

declare(strict_types=1);

namespace Snicco\Listeners;

use Snicco\Events\AdminInit;
use BetterWpHooks\Contracts\Dispatcher;
use Snicco\Events\IncomingAdminRequest;

class CreateDynamicHooks
{
    
    public function __invoke(AdminInit $event, Dispatcher $dispatcher)
    {
        
        $request = $event->request;
        $hook = $event->hook;
        
        $dispatcher->listen("load-{$hook}", function () use ($request) {
            
            IncomingAdminRequest::dispatch([$request]);
            
        });
        
    }
    
}