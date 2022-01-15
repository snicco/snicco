<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Middleware\Internal;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Middleware\Delegate;
use Snicco\HttpRouting\Http\AbstractMiddleware;
use Snicco\HttpRouting\Http\ResponsePreparation;

/**
 * @internal
 */
final class PrepareResponse extends AbstractMiddleware
{
    
    private ResponsePreparation $response_preparation;
    
    public function __construct(ResponsePreparation $response_preparation)
    {
        $this->response_preparation = $response_preparation;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        return $this->response_preparation->prepare(
            $response,
            $request,
        );
    }
    
}