<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
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