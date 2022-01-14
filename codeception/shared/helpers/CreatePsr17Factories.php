<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Nyholm\Psr7\Factory\Psr17Factory;
use Snicco\Core\Http\ResponseFactory;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Core\Http\DefaultResponseFactory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
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