<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Snicco\Events\DoShutdown;
use Snicco\Events\ResponseSent;
use Snicco\Http\Responses\RedirectResponse;

class ResponsePostProcessor
{
    
    private bool $running_unit_tests;
    
    public function __construct(bool $running_unit_tests = false)
    {
        $this->running_unit_tests = $running_unit_tests;
    }
    
    public function maybeExit(ResponseSent $response_sent)
    {
        
        $request = $response_sent->request;
        
        if ($response_sent->response instanceof RedirectResponse) {
            
            $this->terminate($response_sent);
            
        }
        
        // API endpoint requests are always loaded through index.php and thus are
        // also frontend requests.
        if ($request->isWpFrontEnd() || $request->isWpAjax()) {
            
            $this->terminate($response_sent);
            
        }
        
    }
    
    private function terminate(ResponseSent $response_sent)
    {
        
        $terminate = DoShutdown::dispatch([$response_sent->request, $response_sent->response]);
        
        if ($this->running_unit_tests) {
            return;
        }
        
        if (is_bool($terminate) && $terminate !== true) {
            return;
        }
        
        exit();
        
    }
    
}