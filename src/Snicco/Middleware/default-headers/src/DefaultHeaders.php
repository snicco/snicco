<?php

declare(strict_types=1);

namespace Snicco\Middleware\DefaultHeaders;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\NextMiddleware;

/**
 * @api
 */
final class DefaultHeaders extends AbstractMiddleware
{

    private array $default_headers;

    public function __construct(array $default_headers)
    {
        foreach ($default_headers as $name => $value) {
            $this->addHeader($name, $value);
        }
    }

    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        foreach ($this->default_headers as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    private function addHeader(string $name, string $value): void
    {
        $this->default_headers[$name] = $value;
    }

}