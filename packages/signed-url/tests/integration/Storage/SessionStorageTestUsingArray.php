<?php

declare(strict_types=1);

namespace Tests\SignedUrl\integration\Storage;

use Snicco\SignedUrl\Secret;
use Snicco\SignedUrl\UrlSigner;
use Snicco\SignedUrl\Sha256Hasher;
use Codeception\TestCase\WPTestCase;
use Tests\SignedUrl\WithStorageTests;
use Snicco\SignedUrl\Storage\SessionStorage;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

final class SessionStorageTestUsingArray extends WPTestCase
{
    
    use WithStorageTests;
    
    /** @test */
    public function the_storage_array_is_passed_by_reference()
    {
        $arr = [];
        $storage = new SessionStorage($arr);
        
        $signer = new UrlSigner($storage, new Sha256Hasher(Secret::generate()));
        
        $this->assertCount(0, $arr);
        
        $signer->sign('/foo', 10);
        $signer->sign('/bar', 10);
        $signer->sign('/baz', 10);
        
        $this->assertCount(3, $arr[array_key_first($arr)]);
    }
    
    protected function createMagicLinkStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        $arr = [];
        return new SessionStorage($arr, $clock);
    }
    
}
