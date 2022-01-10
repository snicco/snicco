<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Http\Psr7\Request;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Core\Routing\UrlGenerator;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Routing\Internal\Generator;
use Snicco\Core\Http\DefaultResponseFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Core\Routing\Internal\RequestContext;
use Snicco\Core\Routing\Internal\RFC3986Encoder;
use Snicco\Core\Routing\Internal\RouteCollection;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Psr\Http\Message\ServerRequestFactoryInterface;

/**
 * @internal
 */
trait CreatePsr17Factories
{
    
    public static function __callStatic($name, $arguments)
    {
        return static::{$name}($arguments);
    }
    
    public function psrServerRequestFactory() :ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }
    
    public function psrUploadedFileFactory() :UploadedFileFactoryInterface
    {
        return new Psr17Factory();
    }
    
    public function psrUriFactory() :UriFactoryInterface
    {
        return new Psr17Factory();
    }
    
    public function createResponseFactory() :ResponseFactory
    {
        return new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $this->refreshUrlGenerator(),
        );
    }
    
    public function psrResponseFactory() :ResponseFactoryInterface
    {
        return new Psr17Factory();
    }
    
    public function psrStreamFactory() :StreamFactoryInterface
    {
        return new Psr17Factory();
    }
    
    protected function refreshUrlGenerator(RequestContext $context = null) :UrlGenerator
    {
        if (null === $context) {
            $context = $this->request_context ?? new RequestContext(
                    new Request(
                        $this->psrServerRequestFactory()->createServerRequest(
                            'GET',
                            'https://example.com'
                        )
                    ),
                    WPAdminDashboard::fromDefaults(),
                );
        }
        
        return new Generator(
            $this->routes ?? new RouteCollection(),
            $context,
            new RFC3986Encoder()
        );
    }
    
}