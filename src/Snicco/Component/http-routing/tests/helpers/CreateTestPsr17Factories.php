<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;

/**
 * @internal
 *
 * @psalm-internal Snicco\Component\HttpRouting
 */
trait CreateTestPsr17Factories
{
    public static function __callStatic(string $name, array $arguments)
    {
        return static::{$name}($arguments);
    }

    public function psrServerRequestFactory(): ServerRequestFactoryInterface
    {
        return new Psr17Factory();
    }

    public function psrUriFactory(): UriFactoryInterface
    {
        return new Psr17Factory();
    }

    public function psrResponseFactory(): ResponseFactoryInterface
    {
        return new Psr17Factory();
    }

    public function psrStreamFactory(): StreamFactoryInterface
    {
        return new Psr17Factory();
    }

    public function psrUploadedFileFactory(): UploadedFileFactoryInterface
    {
        return new Psr17Factory();
    }

    public function createResponseFactory(): ResponseFactory
    {
        return new ResponseFactory($this->psrResponseFactory(), $this->psrStreamFactory(),);
    }
}
