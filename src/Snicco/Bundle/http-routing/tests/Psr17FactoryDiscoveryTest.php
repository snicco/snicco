<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\Tests;

use GuzzleHttp\Psr7\HttpFactory;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Laminas\Diactoros\UriFactory;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestFactoryInterface;
use RuntimeException;
use Snicco\Bundle\HttpRouting\Psr17FactoryDiscovery;

use function sprintf;

final class Psr17FactoryDiscoveryTest extends TestCase
{
    /**
     * @test
     */
    public function test_auto_discovery_nyholm_psr7(): void
    {
        $discovery = new Psr17FactoryDiscovery([
            Psr17Factory::class => [
                'server_request' => Psr17Factory::class,
                'uri' => Psr17Factory::class,
                'uploaded_file' => Psr17Factory::class,
                'stream' => Psr17Factory::class,
                'response' => Psr17Factory::class,
            ]
        ]);

        $this->assertInstanceOf(Psr17Factory::class, $discovery->createServerRequestFactory());
        $this->assertInstanceOf(Psr17Factory::class, $discovery->createUriFactory());
        $this->assertInstanceOf(Psr17Factory::class, $discovery->createUploadedFileFactory());
        $this->assertInstanceOf(Psr17Factory::class, $discovery->createStreamFactory());
        $this->assertInstanceOf(Psr17Factory::class, $discovery->createResponseFactory());

        $this->assertSame($discovery->createResponseFactory(), $discovery->createUriFactory());
    }

    /**
     * @test
     */
    public function test_auto_discovery_guzzle(): void
    {
        $discovery = new Psr17FactoryDiscovery([
            HttpFactory::class => [
                'server_request' => HttpFactory::class,
                'uri' => HttpFactory::class,
                'uploaded_file' => HttpFactory::class,
                'stream' => HttpFactory::class,
                'response' => HttpFactory::class,
            ]
        ]);

        $this->assertInstanceOf(HttpFactory::class, $discovery->createServerRequestFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createUriFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createUploadedFileFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createStreamFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createResponseFactory());

        $this->assertSame($discovery->createResponseFactory(), $discovery->createUriFactory());
    }

    /**
     * @test
     */
    public function test_auto_discovery_with_both_uses_first(): void
    {
        $discovery = new Psr17FactoryDiscovery([
            HttpFactory::class => [
                'server_request' => HttpFactory::class,
                'uri' => HttpFactory::class,
                'uploaded_file' => HttpFactory::class,
                'stream' => HttpFactory::class,
                'response' => HttpFactory::class,
            ],
            Psr17Factory::class => [
                'server_request' => Psr17Factory::class,
                'uri' => Psr17Factory::class,
                'uploaded_file' => Psr17Factory::class,
                'stream' => Psr17Factory::class,
                'response' => Psr17Factory::class,
            ]
        ]);

        $this->assertInstanceOf(HttpFactory::class, $discovery->createServerRequestFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createUriFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createUploadedFileFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createStreamFactory());
        $this->assertInstanceOf(HttpFactory::class, $discovery->createResponseFactory());

        $this->assertSame($discovery->createResponseFactory(), $discovery->createUriFactory());
    }

    /**
     * @test
     */
    public function test_auto_discovery_laminas(): void
    {
        $discovery = new Psr17FactoryDiscovery([
            ServerRequestFactory::class => [
                'server_request' => ServerRequestFactory::class,
                'uri' => UriFactory::class,
                'uploaded_file' => UploadedFileFactory::class,
                'stream' => StreamFactory::class,
                'response' => ResponseFactory::class,
            ]
        ]);

        $this->assertInstanceOf(ServerRequestFactory::class, $discovery->createServerRequestFactory());
        $this->assertInstanceOf(UriFactory::class, $discovery->createUriFactory());
        $this->assertInstanceOf(UploadedFileFactory::class, $discovery->createUploadedFileFactory());
        $this->assertInstanceOf(StreamFactory::class, $discovery->createStreamFactory());
        $this->assertInstanceOf(ResponseFactory::class, $discovery->createResponseFactory());

        $this->assertNotSame($discovery->createResponseFactory(), $discovery->createUriFactory());
    }

    /**
     * @test
     *
     * @psalm-suppress ArgumentTypeCoercion
     */
    public function test_exception_if_auto_discovery_does_not_work(): void
    {
        // empty array on purpose
        $discovery = new Psr17FactoryDiscovery([
                '\Slim\Psr7\Factory\RequestFactory::class' => [
                    'server_request' => ServerRequestFactory::class,
                    'uri' => UriFactory::class,
                    'uploaded_file' => UploadedFileFactory::class,
                    'stream' => StreamFactory::class,
                    'response' => ResponseFactory::class,
                ]
            ]
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            sprintf('No PSR-17 factory detected to create a %s', ServerRequestFactoryInterface::class)
        );

        $discovery->createServerRequestFactory();
    }

}