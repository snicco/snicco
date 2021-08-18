<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Snicco\Events\Event;
use Snicco\Events\ResponseSent;
use Snicco\Http\Responses\RedirectResponse;

class ShutdownHandler
{
    
    private bool $running_unit_tests;
    
    public function __construct(bool $running_unit_tests = false)
    {
        
        $this->running_unit_tests = $running_unit_tests;
    }
    
    public function unrecoverableException()
    {
        
        $this->terminate();
        
    }
    
    private function terminate()
    {
        
        Event::dispatch('sniccowp.shutdown');
        
        if ($this->running_unit_tests) {
            return;
        }
        
        exit();
        
    }
    
    public function afterResponse(ResponseSent $response_sent)
    {
        
        $request = $response_sent->request;
        
        if ($response_sent->response instanceof RedirectResponse) {
            
            $this->terminate();
            
        }
        
        if ($request->isApiEndPoint() || $request->isWpAjax() || $request->isWpFrontEnd()) {
            
            $this->terminate();
            
        }
        
    }
    
}