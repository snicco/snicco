<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Middleware\Internal;

use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Middleware\Delegate;
use Snicco\HttpRouting\Http\AbstractMiddleware;
use Snicco\HttpRouting\Routing\UrlMatcher\UrlMatcher;
use Snicco\HttpRouting\Routing\Exception\MethodNotAllowed;

/**
 * @internal
 */
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
        
        if ( ! $result->isMatch()) {
            return $next($request);
        }
        
        return $next($request->withRoutingResult($result));
    }
    
}


