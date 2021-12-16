<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class ShareCookies extends Middleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        return $this->addCookiesToResponse($response);
    }
    
    public function addCookiesToResponse(Response $response) :ResponseInterface
    {
        if (($headers = $response->cookies()->toHeaders()) === []) {
            return $response;
        }
        
        foreach ($headers as $header) {
            $response = $response->withAddedHeader('Set-Cookie', $header);
        }
        
        return $response;
    }
    
}