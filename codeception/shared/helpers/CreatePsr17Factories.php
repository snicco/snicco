<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use RuntimeException;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\UrlGenerator;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Routing\RFC3986Encoder;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\DefaultResponseFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Snicco\Core\Routing\UrlGenerationContext;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Core\Contracts\UrlGeneratorInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Core\Contracts\RouteCollectionInterface;

/**
 * @internal
 */
trait CreatePsr17Factories
{
    
    /**
     * @var UrlGeneratorInterface
     */
    protected $generator;
    
    /**
     * @var Redirector
     */
    protected $redirector;
    
    /**
     * @var RouteCollectionInterface
     */
    protected $routes;
    
    /**
     * @var string
     */
    protected $app_domain;
    
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
            $this->newUrlGenerator(),
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
    
    protected function newUrlGenerator(?UrlGenerationContext $context = null, bool $trailing_slash = false) :UrlGenerator
    {
        if ( ! isset($this->app_domain)) {
            throw new RuntimeException('You need to initialize $app_domain.');
        }
        
        if ( ! isset($this->routes)) {
            throw new RuntimeException('You need to initialize $routes.');
        }
        
        $context = $context ?? new UrlGenerationContext(
                new Request(
                    $this->psrServerRequestFactory()->createServerRequest(
                        'GET',
                        'https://'.$this->app_domain
                    )
                ),
                $trailing_slash
            );
        
        $generator = new UrlGenerator(
            $this->routes,
            $context,
            new RFC3986Encoder()
        );
        
        $this->generator = $generator;
        
        return $generator;
    }
    
}