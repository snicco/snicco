<?php

declare(strict_types=1);

namespace Snicco\Middleware\MustMatchRoute;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\Psr7ErrorHandler\HttpException;

final class MustMatchRoute implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        if ($response instanceof DelegatedResponse) {
            throw new HttpException(
                404,
                "A delegated response was returned for path [{$request->getUri()->getPath()}]."
            );
        }

        return $response;
    }
}
