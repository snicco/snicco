<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class DefaultHeaders extends AbstractMiddleware
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