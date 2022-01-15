<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Middleware;

use Snicco\StrArr\Str;
use Psr\Http\Message\ResponseInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\AbstractMiddleware;
use Snicco\HttpRouting\Http\Responses\RedirectResponse;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;

/**
 * @todo Its currently possible to redirect to a whitelisted host from an external referer.
 * @todo It this a problem tho? The only way we could prevent this is to sign all outgoing urls.
 */
final class OpenRedirectProtection extends AbstractMiddleware
{
    
    private string $route;
    
    private array $whitelist;
    
    private string $host;
    
    public function __construct(string $host, $whitelist = [], $route = 'framework.redirect.protection')
    {
        $this->route = $route;
        $this->whitelist = $this->formatWhiteList($whitelist);
        $this->host = $host;
        $this->whitelist[] = $this->allSubdomainsOfApplication();
    }
    
    public function handle(Request $request, Delegate $next) :ResponseInterface
    {
        $response = $next($request);
        
        if ( ! $response->isRedirect()) {
            return $response;
        }
        
        if ($response instanceof RedirectResponse && $response->externalRedirectAllowed()) {
            return $response;
        }
        
        $target = $response->getHeaderLine('location');
        
        $target_host = parse_url($target, PHP_URL_HOST);
        $is_same_site = $this->isSameSiteRedirect($request, $target);
        
        // Allows allow relative redirects
        if ($is_same_site) {
            return $response;
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
                return $this->allSubdomains(Str::afterFirst($pattern, '*.'));
            }
            
            return '/'.preg_quote($pattern, '/').'/';
        }, $whitelist);
    }
    
    private function allSubdomains(string $host) :string
    {
        return '/^(.+\.)?'.preg_quote($host, '/').'$/';
    }
    
    private function allSubdomainsOfApplication() :?string
    {
        if ($host = parse_url($this->host, PHP_URL_HOST)) {
            return $this->allSubdomains($host);
        }
        
        return null;
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
        try {
            return $this->redirect()
                        ->toRoute($this->route, ['intended_redirect' => $location]);
        } catch (RouteNotFound $e) {
            return $this->redirect()->home(['intended_redirect' => $location]);
        }
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