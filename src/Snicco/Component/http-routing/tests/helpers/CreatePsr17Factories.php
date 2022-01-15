<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Snicco\Component\HttpRouting\Http\ResponseFactory;
use Snicco\Component\HttpRouting\Http\DefaultResponseFactory;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

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
    
    public function createResponseFactory(UrlGenerator $generator) :ResponseFactory
    {
        return new DefaultResponseFactory(
            $this->psrResponseFactory(),
            $this->psrStreamFactory(),
            $generator
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
    
}