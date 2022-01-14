<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware;

use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Message\ResponseInterface;
use Snicco\Core\Http\AbstractMiddleware;

/**
 * @api
 */
final class Secure extends AbstractMiddleware
{
    
    private bool $is_local;
    
    public function __construct(bool $is_local = false)
    {
        $this->is_local = $is_local;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        // Don't enforce https in local development mode to allow CI/CD testing.
        if ($this->is_local) {
            return $next($request);
        }
        
        if ( ! $request->isSecure()) {
            $uri = $request->getUri();
            
            // transport security header is ignored for http access, so we don't set it here.
            // @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#description
            $location = $uri->withScheme('https')->__toString();
            
            return $this->respond()->redirect($location, 301);
        }
        
        return $next($request);
    }
    
}