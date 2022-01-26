<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Psr\Http\Message\ServerRequestFactoryInterface;

use function parse_str;
use function array_merge;

/**
 * @api
 */
trait CreatesPsrRequests
{
    
    abstract protected function psrServerRequestFactory() :ServerRequestFactoryInterface;
    
    abstract protected function psrUriFactory() :UriFactoryInterface;
    
    protected function host() :string
    {
        return '127.0.0.1';
    }
    
    final protected function frontendRequest(string $path, array $server = [], string $method = 'GET') :Request
    {
        $method = strtoupper($method);
        $uri = $this->createUri($path);
        
        if ('' === $uri->getHost()) {
            $uri = $uri->withHost($this->host());
        }
        if ('' === $uri->getScheme()) {
            $uri = $uri->withScheme('https');
        }
        
        $request = new Request(
            $this->psrServerRequestFactory()->createServerRequest(
                $method,
                $uri,
                array_merge(['REQUEST_METHOD' => $method], $server),
            )
        );
        
        parse_str($uri->getQuery(), $query);
        
        return $request->withQueryParams($query)->withAttribute(
            Request::TYPE_ATTRIBUTE,
            Request::TYPE_FRONTEND
        );
    }
    
    final protected function adminRequest(string $path, array $server = []) :Request
    {
        return $this->frontendRequest($path, $server, 'GET')
                    ->withAttribute(
                        Request::TYPE_ATTRIBUTE,
                        Request::TYPE_ADMIN_AREA
                    );
    }
    
    private function createUri($uri) :UriInterface
    {
        return $this->psrUriFactory()->createUri($uri);
    }
    
}