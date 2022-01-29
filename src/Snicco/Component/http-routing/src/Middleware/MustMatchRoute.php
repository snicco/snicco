<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\Psr7ErrorHandler\HttpException;
use Snicco\Component\HttpRouting\Http\AbstractMiddleware;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;

/**
 * @api
 */
final class MustMatchRoute extends AbstractMiddleware
{
    
    /**
     * @throws HttpException
     */
    public function handle(Request $request, Delegate $next) :ResponseInterface
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