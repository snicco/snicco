<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Http\Delegate;
use Snicco\View\MethodField;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class MethodOverride extends Middleware
{
    
    private MethodField      $method_field;
    private ContainerAdapter $container;
    
    public function __construct(MethodField $method_field, ContainerAdapter $container)
    {
        $this->method_field = $method_field;
        $this->container = $container;
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
        
        $this->container->instance(Request::class, $request);
        
        return $next($request);
        
    }
    
}