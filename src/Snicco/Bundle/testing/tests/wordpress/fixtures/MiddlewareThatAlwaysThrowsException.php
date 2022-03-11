<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Tests\wordpress\fixtures;

use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

final class MiddlewareThatAlwaysThrowsException extends Middleware
{
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        throw new RuntimeException(MiddlewareThatAlwaysThrowsException::class);
    }
}
