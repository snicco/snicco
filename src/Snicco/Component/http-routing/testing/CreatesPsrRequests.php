<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Testing;

use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

use function array_merge;
use function ltrim;
use function parse_str;
use function strpos;
use function strtoupper;

trait CreatesPsrRequests
{

    final protected function adminRequest(string $path, array $server = []): Request
    {
        return $this->newRequest($path, $server, 'GET', Request::TYPE_ADMIN_AREA);
    }

    final protected function frontendRequest(string $path = '/', array $server = [], string $method = 'GET'): Request
    {
        return $this->newRequest($path, $server, $method, Request::TYPE_FRONTEND);
    }

    abstract protected function psrUriFactory(): UriFactoryInterface;

    protected function host(): string
    {
        return '127.0.0.1';
    }

    abstract protected function psrServerRequestFactory(): ServerRequestFactoryInterface;

    /**
     * @param Request::TYPE_FRONTEND|Request::TYPE_ADMIN_AREA|Request::TYPE_API $type
     */
    private function newRequest(string $path, array $server, string $method, string $type): Request
    {
        if (false === strpos($path, 'http')) {
            $path = '/' . ltrim($path, '/');
        }

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
            ),
            $type
        );

        parse_str($uri->getQuery(), $query);

        return $request->withQueryParams($query);
    }

    private function createUri(string $uri): UriInterface
    {
        return $this->psrUriFactory()->createUri($uri);
    }

}