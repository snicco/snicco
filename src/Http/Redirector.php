<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Contracts\AbstractRedirector;
use Snicco\Http\Responses\RedirectResponse;

class Redirector extends AbstractRedirector
{
    
    public function createRedirectResponse(string $path, int $status_code = 302) :RedirectResponse
    {
        $this->validateStatusCode($status_code);
        
        $psr_response = $this->response_factory->createResponse($status_code);
        
        $response = new RedirectResponse($psr_response);
        
        return $response->to($path);
    }
    
}