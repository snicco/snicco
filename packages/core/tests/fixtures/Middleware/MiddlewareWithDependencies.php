<?php

declare(strict_types=1);

namespace Tests\Core\fixtures\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Codeception\shared\TestDependencies\Foo;

class MiddlewareWithDependencies extends AbstractMiddleware
{
    
    private Foo $foo;
    private Bar $bar;
    
    public function __construct(Foo $foo, Bar $bar)
    {
        $this->foo = $foo;
        $this->bar = $bar;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        $response->getBody()->write(':'.$this->foo->foo.$this->bar->bar);
        return $response;
    }
    
}