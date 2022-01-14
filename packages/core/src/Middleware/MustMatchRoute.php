<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Http\Responses\DelegatedResponse;
use Snicco\Core\ExceptionHandling\Exceptions\NotFoundException;

/**
 * @api
 */
final class MustMatchRoute extends AbstractMiddleware
{
    
    /**
     * @throws NotFoundException
     */
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        if ($response instanceof DelegatedResponse) {
            throw new NotFoundException(
                "A delegated response was returned for request [{$request->fullRequestTarget()}]."
            );
        }
        
        return $response;
    }
    
}