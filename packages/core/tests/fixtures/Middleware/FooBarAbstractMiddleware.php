<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;

class FooBarAbstractMiddleware extends AbstractMiddleware
{
    
    private string $foo;
    private string $bar;
    
    public function __construct($foo = 'foo', $bar = 'bar')
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (isset($request->body)) {
            $request->body .= $this->foo.$this->bar;
            
            return $next($request);
        }
        
        $request->body = $this->foo.$this->bar;
        
        return $next($request);
    }
    
}