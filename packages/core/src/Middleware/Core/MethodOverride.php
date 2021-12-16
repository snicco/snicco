<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Routing\Delegate;
use Snicco\Core\Http\MethodField;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class MethodOverride extends Middleware
{
    
    private MethodField $method_field;
    
    public function __construct(MethodField $method_field)
    {
        $this->method_field = $method_field;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        if ($request->getMethod() !== 'POST' || ! $request->filled('_method')) {
            return $next($request);
        }
        
        $signature = $request->post('_method');
        
        if ( ! $method = $this->method_field->validate($signature)) {
            return $next($request);
        }
        
        $request = $request->withMethod($method);
        
        return $next($request);
    }
    
}