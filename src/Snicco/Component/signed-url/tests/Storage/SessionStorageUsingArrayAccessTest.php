<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests\Storage;

use ArrayAccess;
use PHPUnit\Framework\TestCase;
use ReturnTypeWillChange;
use Snicco\Component\SignedUrl\Hasher\Sha256Hasher;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\Storage\SessionStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Component\TestableClock\Clock;

final class SessionStorageUsingArrayAccessTest extends TestCase
{

    use SignedUrlStorageTests;

    /**
     * @test
     */
    public function the_storage_array_is_passed_by_reference(): void
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

    private function getArrayAccess(): ArrayAccess
    {
        return new class implements ArrayAccess {

            private $container = [];

            #[ReturnTypeWillChange]
            public function offsetSet($offset, $value)
            {
                $this->container[$offset] = $value;
            }

            #[ReturnTypeWillChange]
            public function offsetExists($offset)
            {
                return isset($this->container[$offset]);
            }

            #[ReturnTypeWillChange]
            public function offsetUnset($offset)
            {
                unset($this->container[$offset]);
            }

            #[ReturnTypeWillChange]
            public function offsetGet($offset)
            {
                return $this->container[$offset];
            }

        };
    }

    protected function createStorage(Clock $clock): SignedUrlStorage
    {
        $arr = $this->getArrayAccess();
        return new SessionStorage($arr, $clock);
    }

}