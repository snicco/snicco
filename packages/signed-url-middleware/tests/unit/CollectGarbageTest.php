<?php

declare(strict_types=1);

namespace Tests\SignedUrlMiddleware\unit;

use Snicco\SignedUrl\Secret;
use Psr\Log\Test\TestLogger;
use Snicco\SignedUrl\UrlSigner;
use Tests\Core\MiddlewareTestCase;
use Snicco\SignedUrl\Sha256Hasher;
use Tests\Codeception\shared\TestClock;
use Snicco\SignedUrl\Storage\InMemoryStorage;
use Snicco\SignedUrlMiddleware\CollectGarbage;

final class CollectGarbageTest extends MiddlewareTestCase
{
    
    /** @test */
    public function test_next_is_called()
    {
        $middleware = new CollectGarbage(0, new InMemoryStorage(), new TestLogger());
        
        $this->runMiddleware($middleware, $this->frontendRequest())->assertNextMiddlewareCalled();
    }
    
    /** @test */
    public function garbage_collection_works()
    {
        $signer = new UrlSigner(
            $storage = new InMemoryStorage($test_clock = new TestClock()),
            new Sha256Hasher(Secret::generate())
        );
        
        $link1 = $signer->sign('/foo', 10);
        $link2 = $signer->sign('/bar', 10);
        $link3 = $signer->sign('/baz', 10);
        
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