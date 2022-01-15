<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

/**
 * @api
 */
final class DefaultHeaders extends AbstractMiddleware
{
    
    private array $default_headers;
    
    public function __construct(array $default_headers = ['X-Frame-Options' => 'SAMEORIGIN'])
    {
        $this->default_headers = $default_headers;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        foreach ($this->default_headers as $name => $value) {
            if ( ! $response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }
        
        return $response;
    }
    
}