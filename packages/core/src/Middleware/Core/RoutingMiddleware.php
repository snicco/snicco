<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\UrlMatcher;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Routing\Exceptions\MethodNotAllowed;

final class RoutingMiddleware extends AbstractMiddleware
{
    
    private UrlMatcher $url_matcher;
    
    public function __construct(UrlMatcher $url_matcher)
    {
        $this->url_matcher = $url_matcher;
    }
    
    /**
     * @throws MethodNotAllowed
     */
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $result = $this->url_matcher->dispatch($request);
        
        if ( ! ($route = $result->route())) {
            return $next($request);
        }
        
        return $next($request->withRoutingResult($result));
    }
    
}


