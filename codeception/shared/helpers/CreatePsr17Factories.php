<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\View\ViewEngine;
use Snicco\Http\Psr7\Request;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Snicco\Http\StatelessRedirector;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Testing\TestDoubles\TestMagicLink;
use Psr\Http\Message\ResponseFactoryInterface;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
use Tests\Core\fixtures\TestDoubles\TestViewFactory;

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
        return new ResponseFactory(
            $this->view_engine = new ViewEngine(new TestViewFactory()),
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
        $magic_link = new TestMagicLink();
        
        $this->magic_link = $magic_link;
        
        $generator = new UrlGenerator(
            new FastRouteUrlGenerator($this->routes), $magic_link, $trailing_slash
        );
        
        $this->generator = $generator;
        
        $generator->setRequestResolver(function () use ($request) {
            return $request ?? TestRequest::fromFullUrl('GET', SITE_URL);
        });
        
        return $generator;
    }
    
}