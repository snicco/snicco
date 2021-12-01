<?php

declare(strict_types=1);

namespace Snicco\Testing\Concerns;

use Snicco\Support\Str;
use Snicco\Support\Url;
use Snicco\Http\Psr7\Request;
use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

trait CreatePsrRequests
{
    
    use BuildsWordPressUrls;
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    protected function frontendRequest(string $method = 'GET', $uri = '/') :Request
    {
        $method = strtoupper($method);
        $uri = $this->createUri($uri);
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                $method,
                $uri,
                ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => 'index.php']
            )
        );
        
        parse_str($request->getUri()->getQuery(), $query);
        return $request->withQueryParams($query);
    }
    
    protected function adminRequest(string $method, $menu_slug, $parent = 'admin.php') :Request
    {
        $method = strtoupper($method);
        $url = $this->adminUrlTo($menu_slug, $parent);
        $uri = $this->createUri($url);
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                $method,
                $uri,
                ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => "wp-admin/$parent"]
            )
        );
        
        return $request->withQueryParams(['page' => $menu_slug]);
    }
    
    protected function adminAjaxRequest(string $method, string $action, array $data = []) :Request
    {
        $method = strtoupper($method);
        $uri = $this->createUri($this->ajaxUrl($method === 'GET' ? $action : ''));
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                $method,
                $uri,
                ['REQUEST_METHOD' => $method, 'SCRIPT_NAME' => 'wp-admin/admin-ajax.php']
            )
        );
        
        if ($request->isGet()) {
            return $request->withQueryParams(array_merge(['action' => $action], $data));
        }
        
        return $request->withParsedBody(array_merge(['action' => $action], $data));
    }
    
    private function createUri($uri) :UriInterface
    {
        if (is_string($uri)) {
            if ( ! Str::contains($uri, 'http')) {
                $uri = Url::addLeading($uri);
            }
        }
        
        $uri = $uri instanceof UriInterface
            ? $uri
            : $this->psrUriFactory()->createUri($uri);
        
        if ( ! $uri->getScheme()) {
            $uri = $uri->withScheme('https');
        }
        
        if ( ! $uri->getHost()) {
            $uri = $uri->withHost(
                parse_url(
                    $this->baseUrl(),
                    PHP_URL_HOST
                )
            );
        }
        
        return $uri;
    }
    
}