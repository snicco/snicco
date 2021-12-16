<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

use function in_array;
use function strtoupper;

class MethodOverride extends Middleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ($request->getMethod() !== 'POST' || ! $request->filled('_method')) {
            return $next($request);
        }
        
        $method = $request->body('_method');
        
        if ( ! $this->validMethod($method)) {
            return $next($request);
        }
        
        $request = $request->withMethod($method);
        
        return $next($request);
    }
    
    private function validMethod(string $method) :bool
    {
        $valid = ['PUT', 'PATCH', 'DELETE'];
        
        $method = strtoupper($method);
        
        return in_array($method, $valid);
    }
    
}