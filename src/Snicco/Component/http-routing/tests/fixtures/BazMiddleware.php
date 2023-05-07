<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

final class BazMiddleware extends Middleware
{
    private string $baz;

    public function __construct(string $baz = 'baz_middleware')
    {
        $this->baz = $baz;
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        $body = $response->getBody();
        $body->seek(0, SEEK_END);

        $body->write(':' . $this->baz);

        return $response;
    }
}
