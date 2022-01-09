<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Snicco\EventDispatcher\Contracts\Dispatcher;
use Snicco\Core\EventDispatcher\Events\DoShutdown;
use Snicco\Core\EventDispatcher\Events\ResponseSent;

class ResponsePostProcessor
{
    
    private bool       $running_unit_tests;
    private Dispatcher $event_dispatcher;
    
    public function __construct(Dispatcher $event_dispatcher, $running_unit_tests = false)
    {
        $this->running_unit_tests = $running_unit_tests;
        $this->event_dispatcher = $event_dispatcher;
    }
    
    public function maybeExit(ResponseSent $response_sent)
    {
        $request = $response_sent->request;
        $response = $response_sent->response;
        
        if ($response->isRedirect()) {
            $this->exit($response_sent);
        }
        
        // API endpoint requests are always loaded through index.php and thus are
        // also frontend requests.
        if ($request->isFrontend() || $request->isWpAjax()) {
            $this->exit($response_sent);
        }
        
        if ($request->isToAdminDashboard()
            && ($response->isClientError()
                || $response->isServerError())) {
            $this->exit($response_sent);
        }
    }
    
    private function exit(ResponseSent $response_sent)
    {
        $this->event_dispatcher->dispatch(
            $shutdown = new DoShutdown($response_sent->request, $response_sent->response)
        );
        
        if ($this->running_unit_tests) {
            return;
        }
        
        if ( ! $shutdown->do_shutdown) {
            return;
        }
        
        exit();
    }
    
}