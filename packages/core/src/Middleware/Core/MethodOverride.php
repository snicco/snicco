<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

use function in_array;
use function strtoupper;

final class MethodOverride extends AbstractMiddleware
{
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ('POST' !== ($method = $request->realMethod())) {
            return $next($request);
        }
        
        if ($request->filled('_method')) {
            $method = $request->body('_method');
        }
        elseif ($request->hasHeader('X-HTTP-Method-Override')) {
            $method = $request->getHeaderLine('X-HTTP-Method-Override');
        }
        
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
        
        return in_array($method, $valid, true);
    }
    
}