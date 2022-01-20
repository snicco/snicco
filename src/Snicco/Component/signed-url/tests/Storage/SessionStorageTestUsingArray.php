<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\SignedUrl\Hasher\Sha256Hasher;
use Snicco\Component\SignedUrl\Storage\SessionStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;

use function array_key_first;

final class SessionStorageTestUsingArray extends TestCase
{
    
    use SignedUrlStorageTests;
    
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
    
    protected function createStorage(Clock $clock) :SignedUrlStorage
    {
        $arr = [];
        return new SessionStorage($arr, $clock);
    }
    
}
