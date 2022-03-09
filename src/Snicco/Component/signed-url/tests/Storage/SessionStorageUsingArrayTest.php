<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests\Storage;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\SignedUrl\HMAC;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\Storage\SessionStorage;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Component\TestableClock\Clock;

use function array_key_first;

final class SessionStorageUsingArrayTest extends TestCase
{
    use SignedUrlStorageTests;

    /**
     * @test
     *
     * @psalm-suppress MixedArgument
     * @psalm-suppress PossiblyNullArrayOffset
     */
    public function the_storage_array_is_passed_by_reference(): void
    {
        $arr = [];
        $storage = new SessionStorage($arr);

        $signer = new UrlSigner($storage, new HMAC(Secret::generate()));

        /** @var array $arr */
        $this->assertCount(0, $arr);

        $signer->sign('/foo', 10);
        $signer->sign('/bar', 10);
        $signer->sign('/baz', 10);

        $this->assertCount(3, $arr[array_key_first($arr)]);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_for_non_array_non_array_access(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$storage must be an array or instance of ArrayAccess');
        $str = '';
        new SessionStorage($str);
    }

    protected function createStorage(Clock $clock): SignedUrlStorage
    {
        $arr = [];
        return new SessionStorage($arr, $clock);
    }
}
