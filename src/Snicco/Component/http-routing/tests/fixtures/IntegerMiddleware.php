<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

final class IntegerMiddleware extends Middleware
{
    private string $val;

    public function __construct(int $val)
    {
        $this->val = 'integer_' . (string) $val;
    }

    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        $response->getBody()
            ->write(':' . $this->val);

        return $response;
    }
}
