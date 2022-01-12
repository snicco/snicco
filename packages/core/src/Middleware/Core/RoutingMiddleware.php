<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Core;

use Webmozart\Assert\Assert;
use Snicco\Core\Http\Delegate;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Contracts\AbstractMiddleware;
use Snicco\Core\Routing\UrlMatcher\UrlMatcher;
use Snicco\Core\Routing\Exception\MethodNotAllowed;

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
        $real_request = $request;
        $routing_request = $request;
        
        $routing_uri = $request->getAttribute(AllowMatchingAdminRoutes::REWRITTEN_URI);
        
        if ( ! is_null($routing_uri)) {
            Assert::isInstanceOf($routing_uri, UriInterface::class);
            $routing_request = $real_request->withUri($routing_uri);
        }
        
        $result = $this->url_matcher->dispatch($routing_request);
        
        if ( ! $result->isMatch()) {
            return $next($real_request);
        }
        
        return $next($real_request->withRoutingResult($result));
    }
    
}


