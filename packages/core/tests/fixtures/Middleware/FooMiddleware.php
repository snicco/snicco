<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

class FooMiddleware extends Middleware
{
    
    private string $foo;
    
    public function __construct($foo = 'foo')
    {
        $this->foo = $foo;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        if (isset($request->body)) {
            $request->body .= $this->foo;
            
            return $next($request);
        }
        
        $request->body = $this->foo;
        
        return $next($request);
    }
    
}