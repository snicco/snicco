<?php

declare(strict_types=1);

namespace Snicco\Middleware\TrailingSlash;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;
use Snicco\Component\StrArr\Str;

use function rtrim;

final class TrailingSlash extends Middleware
{
    private bool $trailing_slash;

    public function __construct(bool $trailing_slash = false)
    {
        $this->trailing_slash = $trailing_slash;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $path = $request->path();

        if ('/' === $path) {
            return $next($request);
        }

        $accept_request = $this->trailing_slash
            ? Str::endsWith($path, '/')
            : Str::doesNotEndWith($path, '/');

        if ($accept_request) {
            return $next($request);
        }

        $redirect_to = $this->trailing_slash
            ? $path . '/'
            : rtrim($path, '/');

        return $this->responseFactory()
            ->redirect($redirect_to, 301);
    }
}
