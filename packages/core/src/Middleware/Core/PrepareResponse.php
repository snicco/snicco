<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Core\Contracts\AbstractMiddleware;

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