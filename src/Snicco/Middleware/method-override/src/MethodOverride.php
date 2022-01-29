<?php

declare(strict_types=1);

namespace Snicco\Middleware\MethodOverride;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Delegate;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;

use function in_array;
use function strtoupper;

/**
 * @api
 */
final class MethodOverride extends AbstractMiddleware
{
    
    const HEADER = 'X-HTTP-Method-Override';
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ('POST' !== ($method = $request->realMethod())) {
            return $next($request);
        }
        
        if ($request->filled('_method')) {
            $method = $request->body('_method');
        }
        elseif ($request->hasHeader(self::HEADER)) {
            $method = $request->getHeaderLine(self::HEADER);
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