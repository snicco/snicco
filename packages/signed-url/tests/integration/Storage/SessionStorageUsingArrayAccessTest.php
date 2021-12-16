<?php

declare(strict_types=1);

namespace Tests\SignedUrl\integration\Storage;

use ArrayAccess;
use Snicco\SignedUrl\Secret;
use Snicco\SignedUrl\UrlSigner;
use Snicco\SignedUrl\Sha256Hasher;
use Codeception\TestCase\WPTestCase;
use Tests\SignedUrl\WithStorageTests;
use Snicco\SignedUrl\Storage\SessionStorage;
use Snicco\SignedUrl\Contracts\SignedUrlClock;
use Snicco\SignedUrl\Contracts\SignedUrlStorage;

final class SessionStorageUsingArrayAccessTest extends WPTestCase
{
    
    use WithStorageTests;
    
    /** @test */
    public function the_storage_array_is_passed_by_reference()
    {
        $arr = $this->getArrayAccess();
        $storage = new SessionStorage($arr);
        
        $signer = new UrlSigner($storage, new Sha256Hasher(Secret::generate()));
        
        $this->assertArrayNotHasKey('_singed_urls', $arr);
        
        $url1 = $signer->sign('/foo', 10);
        $url2 = $signer->sign('/bar', 10);
        $url3 = $signer->sign('/baz', 10);
        
        $urls = $arr['_signed_urls'];
        
        $this->assertArrayHasKey($url1->identifier(), $urls);
        $this->assertArrayHasKey($url2->identifier(), $urls);
        $this->assertArrayHasKey($url3->identifier(), $urls);
    }
    
    protected function createMagicLinkStorage(SignedUrlClock $clock) :SignedUrlStorage
    {
        $arr = $this->getArrayAccess();
        return new SessionStorage($arr, $clock);
    }
    
    private function getArrayAccess() :ArrayAccess
    {
        return new class implements ArrayAccess
        {
            
            private $container = [];
            
            public function offsetSet($offset, $value)
            {
                $this->container[$offset] = $value;
            }
            
            public function offsetExists($offset)
            {
                return isset($this->container[$offset]);
            }
            
            public function offsetUnset($offset)
            {
                unset($this->container[$offset]);
            }
            
            public function offsetGet($offset)
            {
                return $this->container[$offset];
            }
            
        };
    }
    
}