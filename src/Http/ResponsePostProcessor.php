<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Events\DoShutdown;
use Snicco\Events\ResponseSent;

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
        $response = $response_sent->response;
        
        if ($response->isRedirect()) {
            $this->exit($response_sent);
        }
        
        // API endpoint requests are always loaded through index.php and thus are
        // also frontend requests.
        if ($request->isWpFrontEnd() || $request->isWpAjax()) {
            $this->exit($response_sent);
        }
        
        if ($request->isWpAdmin() && ($response->isClientError() || $response->isServerError())) {
            $this->exit($response_sent);
        }
    }
    
    private function exit(ResponseSent $response_sent)
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