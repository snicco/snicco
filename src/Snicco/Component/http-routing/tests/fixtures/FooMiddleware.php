<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;

class FooMiddleware extends Middleware
{

    public string $foo;

    public function __construct(string $foo = 'foo_middleware')
    {
        $this->foo = $foo;
    }

    public function handle(Request $request, $next): ResponseInterface
    {
        $response = $next($request);

        $response->getBody()->write(':' . $this->foo);
        return $response;
    }

}