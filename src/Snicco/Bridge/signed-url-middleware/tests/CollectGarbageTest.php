<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlMiddleware\Tests;

use Psr\Log\Test\TestLogger;
use Snicco\Bridge\SignedUrlMiddleware\CollectGarbage;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\HMAC;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Component\TestableClock\TestClock;

final class CollectGarbageTest extends MiddlewareTestCase
{
    /**
     * @test
     */
    public function test_next_is_called(): void
    {
        $middleware = new CollectGarbage(0, new InMemoryStorage(), $logger = new TestLogger());

        $this->runMiddleware($middleware, $this->frontendRequest())->assertNextMiddlewareCalled();
        $this->assertCount(0, $logger->records);
    }

    /**
     * @test
     */
    public function garbage_collection_works(): void
    {
        $signer = new UrlSigner(
            $storage = new InMemoryStorage($test_clock = new TestClock()),
            new HMAC(Secret::generate())
        );

        $signer->sign('/foo', 10);
        $signer->sign('/bar', 10);
        $signer->sign('/baz', 10);

        $this->assertCount(3, $storage->all());

        $middleware = new CollectGarbage(100, $storage, new TestLogger());

        $test_clock->travelIntoFuture(10);
        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        $response->assertNextMiddlewareCalled();

        $this->assertCount(3, $storage->all());

        $test_clock->travelIntoFuture(1);
        $response = $this->runMiddleware($middleware, $this->frontendRequest());
        $response->assertNextMiddlewareCalled();

        $this->assertCount(0, $storage->all());
    }

    /**
     * @test
     */
    public function errors_are_logged_if_garbage_collection_fails(): void
    {
        $storage = new class() implements SignedUrlStorage {
            public function consume(string $identifier): void
            {
                //
            }

            public function store(SignedUrl $signed_url): void
            {
                //
            }

            public function gc(): void
            {
                throw new UnavailableStorage('GC fail.');
            }
        };

        $middleware = new CollectGarbage(100, $storage, $logger = new TestLogger());

        $this->runMiddleware($middleware, $this->frontendRequest())->assertNextMiddlewareCalled();

        $this->assertTrue(
            $logger->hasError([
                'message' => 'GC fail.'
            ])
        );
    }
}
