<?php

declare(strict_types=1);

namespace Tests\SignedUrl;

use Snicco\SignedUrl\SignedUrl;
use PHPUnit\Framework\Assert as PHPUnit;
use Tests\Codeception\shared\TestClock;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Exceptions\BadIdentifier;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;
use Snicco\SignedUrl\SignedUrlClockUsingDateTimeImmutable;

trait WithStorageTests
{
    
    /** @test */
    public function a_magic_link_can_be_stored()
    {
        $storage = $this->createMagicLinkStorage(new SignedUrlClockUsingDateTimeImmutable());
        
        $storage->store(
            $valid = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature')
        );
        
        PHPUnit::assertSame(1, $storage->remainingUsage($valid->identifier()));
        
        $invalid = $this->createSignedUrl('/bar?signature=bar_signature', 'bar_signature');
        
        PHPUnit::assertSame(0, $storage->remainingUsage($invalid->identifier()));
    }
    
    /** @test */
    public function garbage_collection_works()
    {
        $storage = $this->createMagicLinkStorage($clock = new TestClock());
        
        $storage->store(
            $link1 = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature', 9)
        );
        
        $storage->store(
            $link2 = $this->createSignedUrl('/bar?signature=bar_signature', 'bar_signature', 10)
        );
        
        $clock->travelIntoFuture(9);
        
        $storage->gc();
        
        PHPUnit::assertSame(1, $storage->remainingUsage($link1->identifier()));
        PHPUnit::assertSame(1, $storage->remainingUsage($link2->identifier()));
        
        $clock->travelIntoFuture(1);
        $storage->gc();
        
        PHPUnit::assertSame(0, $storage->remainingUsage($link1->identifier()));
        PHPUnit::assertSame(1, $storage->remainingUsage($link2->identifier()));
        
        $clock->travelIntoFuture(1);
        $storage->gc();
        
        PHPUnit::assertSame(0, $storage->remainingUsage($link1->identifier()));
        PHPUnit::assertSame(0, $storage->remainingUsage($link2->identifier()));
    }
    
    /** @test */
    public function consuming_a_magic_link_works()
    {
        $storage = $this->createMagicLinkStorage($clock = new TestClock());
        
        $storage->store(
            $link = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature', 10, 3)
        );
        
        $signature = $link->identifier();
        PHPUnit::assertSame(3, $storage->remainingUsage($signature));
        
        $storage->decrementUsage($signature);
        PHPUnit::assertSame(2, $storage->remainingUsage($signature));
        
        $storage->decrementUsage($signature);
        PHPUnit::assertSame(1, $storage->remainingUsage($signature));
        
        $storage->decrementUsage($signature);
        PHPUnit::assertSame(0, $storage->remainingUsage($signature));
    }
    
    /** @test */
    public function consuming_a_missing_signature_throws_an_exception()
    {
        $storage = $this->createMagicLinkStorage($clock = new TestClock());
        
        $storage->store(
            $link = $this->createSignedUrl('/foo?signature=foo_signature', 'foo_signature', 10, 3)
        );
        
        $signature = $link->identifier().'XXX';
    
        try {
            $storage->decrementUsage($signature);
            PHPUnit::fail("Expected exception to be thrown");
        } catch (BadIdentifier $e) {
            PHPUnit::assertStringStartsWith(
                "The identifier [$signature] does not exist",
                $e->getMessage()
            );
        }
    }
    
    abstract protected function createMagicLinkStorage(SignedUrlClock $clock) :SignedUrlStorage;
    
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