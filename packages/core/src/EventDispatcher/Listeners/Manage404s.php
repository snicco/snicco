<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Listeners;

use Snicco\EventDispatcher\Events\PreWP404;
use Snicco\EventDispatcher\Contracts\Dispatcher;

/**
 * This event listener will prevent that WordPress handles any 404s before we had a chance
 * to match the URL against our routes.
 */
class Manage404s
{
    
    private Dispatcher $dispatcher;
    
    public function __construct(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }
    
    public function shortCircuit(PreWP404 $pre_WP_404)
    {
        $pre_WP_404->process_404 = false;
    }
    
    public function check404()
    {
        global $wp;
        
        $this->dispatcher->remove(PreWP404::class);
        
        $wp->handle_404();
    }
    
}