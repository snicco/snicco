<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Http\Responses\RedirectResponse;

class StatelessRedirector extends Redirector
{
    
    public function createRedirectResponse(string $path, int $status_code = 302) :RedirectResponse
    {
        $this->validateStatusCode($status_code);
        
        $psr_response = $this->response_factory->createResponse($status_code);
        
        $response = new RedirectResponse($psr_response);
        
        return $response->to($path);
    }
    
}