<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;

class FooMiddleware extends AbstractMiddleware
{
    
    public string $foo;
    
    public function __construct($foo = 'foo_middleware')
    {
        $this->foo = $foo;
    }
    
    public function handle(Request $request, $next) :ResponseInterface
    {
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->foo);
        return $response;
    }
    
}