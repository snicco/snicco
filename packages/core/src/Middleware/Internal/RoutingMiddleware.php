<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Routing\UrlMatcher\UrlMatcher;
use Snicco\Core\Routing\Exception\MethodNotAllowed;

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


