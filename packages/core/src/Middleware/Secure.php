<?php

declare(strict_types=1);

namespace Snicco\Middleware;

use Snicco\Support\Url;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;

/**
 * @todo tests.
 */
class Secure extends Middleware
{
    
    const HEADER = 'Strict-Transport-Security';
    
    protected bool $preload;
    protected int  $max_age;
    protected bool $subdomains;
    private bool   $is_local;
    
    public function __construct(bool $is_local = false, int $max_age = 31536000, $preload = false, $subdomains = false)
    {
        $this->max_age = $max_age;
        $this->preload = $preload;
        $this->subdomains = $subdomains;
        $this->is_local = $is_local;
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        // Don't enforce https in local development mode to allow either CI/CD testing.
        if ($this->is_local) {
            return $next($request);
        }
        
        $uri = $request->getUri();
        
        if ( ! $this->isSecure($request)) {
            // transport security header is ignored for http access.
            // @link https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Strict-Transport-Security#description
            $location = $uri->withScheme('https')->__toString();
            
            return $this->response_factory->redirect()
                                          ->secure($location);
        }
        
        $response = $next($request);
        
        if ( ! $response->hasHeader(self::HEADER)) {
            $response = $this->addStrictTransportPolicy($response);
        }
        
        if ( ! $response->isRedirect()) {
            return $response;
        }
        
        $location = parse_url($response->getHeaderLine('Location'));
        
        if ( ! isset($location['host']) || $location['host'] !== $uri->getHost()) {
            return $response;
        }
        
        $location['scheme'] = 'https';
        unset($location['port']);
        
        return $response->withHeader('Location', Url::unParseUrl($location));
    }
    
    /** @todo Move this to the request class as soon as we have support for trusted proxies. */
    private function isSecure(Request $request) :bool
    {
        if (strtolower($request->getUri()->getScheme()) === 'https') {
            return true;
        }
        
        if ($request->server('HTTPS') === 'on') {
            return true;
        }
        
        if ($request->server('HTTP_X_FORWARDED_PROTO') === 'https') {
            return true;
        }
        
        if ($request->server('HTTP_X_FORWARDED_SSL') === 'on') {
            return true;
        }
        
        return false;
    }
    
    private function addStrictTransportPolicy(Response $response) :Response
    {
        $header = sprintf(
            'max-age=%d%s%s',
            $this->max_age,
            $this->subdomains ? ';includeSubDomains' : '',
            $this->preload ? ';preload' : ''
        );
        
        return $response->withHeader(self::HEADER, $header);
    }
    
}