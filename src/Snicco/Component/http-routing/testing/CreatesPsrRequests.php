<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function parse_url;
use function parse_str;
use function array_merge;

/**
 * @api
 */
trait CreatesPsrRequests
{
    
    use CreatesUrls;
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    final protected function frontendRequest(string $path, array $server = [], string $method = 'GET') :Request
    {
        $parts = parse_url($path);
        $extra = [];
        $query = [];
        
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $extra = array_merge($extra, $query);
        }
        
        if (isset($parts['fragment'])) {
            $extra = array_merge($extra, ['_fragment' => $parts['fragment']]);
        }
        
        $uri = $this->frontendUrl($parts['path'], $extra);
        
        $method = strtoupper($method);
        $uri = $this->createUri($uri);
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                $method,
                $uri,
                array_merge(['REQUEST_METHOD' => $method], $server),
            )
        );
        
        return $request->withQueryParams($query)->withAttribute(
            Request::TYPE_ATTRIBUTE,
            Request::TYPE_FRONTEND
        );
    }
    
    final protected function adminRequest(string $path, array $server = []) :Request
    {
        $parts = parse_url($path);
        $extra = [];
        
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
            $extra = array_merge($extra, $query);
        }
        
        if (isset($parts['fragment'])) {
            $extra = array_merge($extra, ['_fragment' => $parts['fragment']]);
        }
        
        $uri = $this->adminUrl($parts['path'], $extra);
        $uri = $this->createUri($uri);
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                'GET',
                $uri,
                array_merge(['REQUEST_METHOD' => 'GET'], $server),
            )
        );
        
        parse_str($request->getUri()->getQuery(), $query);
        
        return $request->withQueryParams($query)->withAttribute(
            Request::TYPE_ATTRIBUTE,
            Request::TYPE_ADMIN_AREA
        );
    }
    
    private function createUri($uri) :UriInterface
    {
        return $this->psrUriFactory()->createUri($uri);
    }
    
}