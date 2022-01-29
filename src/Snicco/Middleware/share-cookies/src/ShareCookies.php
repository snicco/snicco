<?php

declare(strict_types=1);

namespace Snicco\Middleware\ShareCookies;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Middleware\Delegate;
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
        $cookie_headers = $response->cookies()->toHeaders();
        
        if ($cookie_headers === []) {
            return $response;
        }
        
        foreach ($cookie_headers as $header) {
            $response = $response->withAddedHeader('set-cookie', $header);
        }
        
        return $response;
    }
    
}