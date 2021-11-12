<?php

declare(strict_types=1);

namespace Snicco\Middleware\Core;

use Snicco\Support\Str;
use Snicco\Http\Delegate;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\Middleware;
use Psr\Http\Message\ResponseInterface;
use Snicco\Http\Responses\RedirectResponse;

class OpenRedirectProtection extends Middleware
{
    
    private string $route;
    private array  $whitelist;
    private string $site_url;
    
    public function __construct(string $site_url, $whitelist = [], $route = 'redirect.protection')
    {
        $this->route = $route;
        $this->whitelist = $this->formatWhiteList($whitelist);
        $this->site_url = $site_url;
        $this->whitelist[] = $this->allSubdomainsOfApplicationUrl();
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        if ( ! $response->isRedirect()) {
            return $response;
        }
        
        if (method_exists($response, 'canBypassValidation') && $response->canBypassValidation()) {
            return $response;
        }
        
        $target = $response->getHeaderLine('location');
        
        $target_host = parse_url($target, PHP_URL_HOST);
        $is_same_ref = $this->isSameSiteReferer($request);
        $is_same_site = $this->isSameSiteRedirect($request, $target);
        
        // Allows allow relative redirects
        if ($is_same_site) {
            return $response;
        }
        
        // Don't allow external domains to redirect to another external domain.
        if ( ! $is_same_ref) {
            return $this->forbiddenRedirect($target);
        }
        
        // Only allow redirects away to whitelisted hosts.
        if ($this->isWhitelisted($target_host)) {
            return $response;
        }
        
        return $this->forbiddenRedirect($target);
    }
    
    private function formatWhiteList(array $whitelist) :array
    {
        return array_map(function ($pattern) {
            if (Str::startsWith($pattern, '*.')) {
                return $this->allSubdomains(Str::after($pattern, '*.'));
            }
            
            return '/'.preg_quote($pattern, '/').'/';
        }, $whitelist);
    }
    
    private function allSubdomains(string $host) :string
    {
        return '/^(.+\.)?'.preg_quote($host, '/').'$/';
    }
    
    private function allSubdomainsOfApplicationUrl() :?string
    {
        if ($host = parse_url($this->site_url, PHP_URL_HOST)) {
            return $this->allSubdomains($host);
        }
        
        return null;
    }
    
    private function isSameSiteReferer(Request $request) :bool
    {
        $referer = parse_url($request->getHeaderLine('referer'), PHP_URL_HOST);
        
        if ( ! $referer) {
            return false;
        }
        
        return $referer === $request->getUri()->getHost();
    }
    
    private function isSameSiteRedirect(Request $request, $location) :bool
    {
        $parsed = parse_url($location);
        $target = $parsed['host'] ?? null;
        
        if ( ! $target && $parsed['path']) {
            return true;
        }
        
        return $target === $request->getUri()->getHost();
    }
    
    private function forbiddenRedirect($location) :RedirectResponse
    {
        return $this->response_factory->redirect()
                                      ->toTemporarySignedRoute(
                                          $this->route,
                                          10,
                                          ['query' => ['intended_redirect' => $location]]
                                      );
    }
    
    private function isWhitelisted($host) :bool
    {
        if (in_array($host, $this->whitelist)) {
            return true;
        }
        
        foreach ($this->whitelist as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }
        
        return false;
    }
    
}