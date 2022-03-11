<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Middleware\Middleware;
use Snicco\Component\HttpRouting\Middleware\NextMiddleware;

use function extract;
use function ob_get_clean;
use function ob_start;

use const EXTR_SKIP;

final class SimpleTemplating extends Middleware
{
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        if (! $response instanceof ViewResponse) {
            return $response;
        }

        $view = $response->view();
        $data = $response->viewData();

        $body = (static function () use ($view, $data): string {
            extract($data, EXTR_SKIP);

            ob_start();

            /** @psalm-suppress UnresolvableInclude */
            require $view;

            return (string) ob_get_clean();
        })();

        // Wrap the response inside a normal response so that other middleware does not render it again.
        return (new Response($response))->withBody($this->responseFactory()->createStream($body));
    }
}
