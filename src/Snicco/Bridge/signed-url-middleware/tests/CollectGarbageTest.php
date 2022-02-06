<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlMiddleware\Tests;

use Psr\Log\Test\TestLogger;
use Snicco\Bridge\SignedUrlMiddleware\CollectGarbage;
use Snicco\Component\HttpRouting\Testing\MiddlewareTestCase;
use Snicco\Component\SignedUrl\Hasher\Sha256Hasher;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Component\TestableClock\TestClock;

final class CollectGarbageTest extends MiddlewareTestCase
{

    /**
     * @test
     */
    public function test_next_is_called(): void
    {
        $middleware = new CollectGarbage(0, new InMemoryStorage(), new TestLogger());

        $this->runMiddleware($middleware, $this->frontendRequest())->assertNextMiddlewareCalled();
    }

    /**
     * @test
     */
    public function garbage_collection_works(): void
    {
        $signer = new UrlSigner(
            $storage = new InMemoryStorage($test_clock = new TestClock()),
            new Sha256Hasher(Secret::generate())
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

}