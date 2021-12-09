<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\Responses\NullResponse;
use Snicco\Core\Http\Responses\DelegatedResponse;
use Snicco\Core\ExceptionHandling\Exceptions\NotFoundException;

class EvaluateResponseMiddleware extends Middleware
{
    
    private bool $must_match_web_routes;
    
    public function __construct(bool $must_match_web_routes = false)
    {
        $this->must_match_web_routes = $must_match_web_routes;
    }
    
    /**
     * @throws NotFoundException
     */
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        if ($this->must_match_web_routes && $request->isWpFrontEnd()) {
            if ($response instanceof NullResponse || $response instanceof DelegatedResponse) {
                throw new NotFoundException("404 for request path [{$request->fullPath()}]");
            }
        }
        
        return $response;
    }
    
}