<?php

declare(strict_types=1);

namespace Snicco\Middleware\MustMatchRoute;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\NextMiddleware;
use Snicco\Component\Psr7ErrorHandler\HttpException;

/**
 * @api
 */
final class MustMatchRoute extends AbstractMiddleware
{

    /**
     * @throws HttpException
     */
    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $response = $next($request);

        if ($response instanceof DelegatedResponse) {
            throw new HttpException(
                404,
                "A delegated response was returned for path [{$request->path()}]."
            );
        }

        return $response;
    }

}