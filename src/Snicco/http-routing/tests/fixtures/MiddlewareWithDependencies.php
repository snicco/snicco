<?php

declare(strict_types=1);

namespace Tests\HttpRouting\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Middleware\Delegate;
use Snicco\HttpRouting\Http\AbstractMiddleware;
use Tests\Codeception\shared\TestDependencies\Bar;
use Tests\Codeception\shared\TestDependencies\Foo;

class MiddlewareWithDependencies extends AbstractMiddleware
{
    
    public Foo $foo;
    public Bar $bar;
    
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