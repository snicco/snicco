<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;
use Snicco\Component\SignedUrl\Exception\BadIdentifier;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;

trait SignedUrlStorageTests
{
    
    /** @test */
    public function garbage_collection_works()
    {
        $storage = $this->createStorage($clock = new TestClock());
        
        $storage->store(
            $link1 = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature', 9, 10)
        );
        
        $storage->store(
            $link2 = $this->createSignedUrl('/bar?signature=bar_signature', 'bar_signature', 10, 10)
        );
        
        $clock->travelIntoFuture(9);
        
        $storage->gc();
        
        // both valid
        $storage->consume($link1->identifier());
        $storage->consume($link2->identifier());
        
        $clock->travelIntoFuture(1);
        $storage->gc();
        
        try {
            // first one invalid
            $storage->consume($link1->identifier());
            PHPUnit::fail("Garbage collection did not remove an expired link");
        } catch (BadIdentifier $e) {
            PHPUnit::assertStringContainsString($link1->identifier(), $e->getMessage());
        }
        // still valid
        $storage->consume($link2->identifier());
        
        $clock->travelIntoFuture(1);
        $storage->gc();
        
        try {
            // second one invalid
            $storage->consume($link2->identifier());
            PHPUnit::fail('Garbage collection did not remove an expired link');
        } catch (BadIdentifier $e) {
            PHPUnit::assertStringContainsString($link2->identifier(), $e->getMessage());
        }
    }
    
    /** @test */
    public function the_url_is_removed_from_storage_after_the_last_max_usages()
    {
        $storage = $this->createStorage(new TestClock());
        
        $storage->store(
            $signed = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature', 10, 3)
        );
        
        $storage->consume($id = $signed->identifier());
        $storage->consume($id = $signed->identifier());
        $storage->consume($id = $signed->identifier());
        
        try {
            $storage->consume($id);
            PHPUnit::fail("Decrementing a used signed url below 0 should throw an exception");
        } catch (BadIdentifier $e) {
            PHPUnit::assertStringStartsWith(
                "The identifier [$id] does not exist",
                $e->getMessage()
            );
        }
    }
    
    /** @test */
    public function decrementing_a_missing_signature_throws_an_exception()
    {
        $storage = $this->createStorage($clock = new TestClock());
        
        $storage->store(
            $link = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature', 10, 3)
        );
        
        $signature = $link->identifier().'XXX';
        
        try {
            $storage->consume($signature);
            PHPUnit::fail("Expected exception to be thrown");
        } catch (BadIdentifier $e) {
            PHPUnit::assertStringStartsWith(
                "The identifier [$signature] does not exist",
                $e->getMessage()
            );
        }
    }
    
    abstract protected function createStorage(Clock $clock) :SignedUrlStorage;
    
    protected function createSignedUrl(string $link_target, string $signature, int $expires_in = 10, $max_usage = 1) :SignedUrl
    {
        return SignedUrl::create(
            $link_target,
            $link_target,
            $signature,
            time() + $expires_in,
            $max_usage
        );
    }
    
}