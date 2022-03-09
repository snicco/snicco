<?php

declare(strict_types=1);

namespace Snicco\Middleware\DefaultHeaders;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class DefaultHeaders implements MiddlewareInterface
{
    /**
     * @var  array<string,string>
     */
    private array $default_headers;

    /**
     * @param array<string,string> $default_headers
     */
    public function __construct(array $default_headers)
    {
        $this->default_headers = $default_headers;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->default_headers as $name => $value) {
            if (!$response->hasHeader($name)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
