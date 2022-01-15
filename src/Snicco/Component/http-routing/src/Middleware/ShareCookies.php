<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

/**
 * @api
 */
final class ShareCookies extends AbstractMiddleware
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