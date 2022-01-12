<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\ResponsePreparation;
use Snicco\Core\Contracts\AbstractMiddleware;

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