<?php

declare(strict_types=1);

namespace Snicco\Middleware\HttpsOnly;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

final class HttpsOnly extends Middleware
{
    private bool $is_local;

    public function __construct(bool $is_local = false)
    {
        $this->is_local = $is_local;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        // Don't enforce https in local development mode to allow CI/CD testing.
        if ($this->is_local) {
            return $next($request);
        }

        if (! $request->isSecure()) {
            $uri = $request->getUri();

            /**
             * transport security header is ignored for http access, so we don't
             * set it here.
             *
             * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#description
             */
            $location = $uri->withScheme('https')
                ->__toString();

            return $this->responseFactory()
                ->redirect($location, 301);
        }

        return $next($request);
    }
}
