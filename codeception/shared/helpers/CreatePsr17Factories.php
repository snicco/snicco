<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use RuntimeException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Core\Routing\UrlGenerator;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Http\DefaultResponseFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Core\Routing\Internal\RequestContext;
use Psr\Http\Message\UploadedFileFactoryInterface;
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
        throw new RuntimeException("Overwrite this method in a test case that uses this trait");
    }
    
}