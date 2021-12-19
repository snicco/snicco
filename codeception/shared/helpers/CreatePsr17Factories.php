<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\UrlGenerator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Core\Http\BaseResponseFactory;
use Snicco\Core\Http\StatelessRedirector;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Routing\InMemoryMagicLink;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Core\Routing\FastRoute\FastRouteUrlGenerator;

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
        return new BaseResponseFactory(
            $f = $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            new StatelessRedirector($this->newUrlGenerator(), $f),
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
    
    protected function newUrlGenerator(Request $request = null, bool $trailing_slash = false) :UrlGenerator
    {
        $generator = new UrlGenerator(
            new FastRouteUrlGenerator($this->routes), $trailing_slash
        );
        
        $this->generator = $generator;
        
        $generator->setRequestResolver(function () use ($request) {
            return $request ?? TestRequest::fromFullUrl('GET', SITE_URL);
        });
        
        return $generator;
    }
    
}