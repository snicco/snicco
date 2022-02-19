<?php

declare(strict_types=1);

namespace Snicco\Component\SignedUrl\Tests;

use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\SignedUrl\GarbageCollector;
use Snicco\Component\SignedUrl\Hasher\Sha256HMAC;
use Snicco\Component\SignedUrl\HMAC;
use Snicco\Component\SignedUrl\Secret;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\InMemoryStorage;
use Snicco\Component\SignedUrl\UrlSigner;
use Snicco\Component\TestableClock\TestClock;

final class UrlSignerTest extends TestCase
{

    private UrlSigner $url_signer;
    private InMemoryStorage $storage;

    protected function setUp(): void
    {
        parent::setUp();
        $this->storage = new InMemoryStorage();
        $this->url_signer =
            new UrlSigner($this->storage, new HMAC(Secret::generate()));
    }

    /**
     * @test
     */
    public function test_exception_if_the_path_contains_the_expired_query_param(): void
    {
        $this->expectException(LogicException::class);
        $this->url_signer->sign('/foo?expires=100', 10);
    }

    /**
     * @test
     */
    public function test_exception_if_the_path_contains_the_signature_query_param(): void
    {
        $this->expectException(LogicException::class);
        $this->url_signer->sign('/foo?signature=100', 10);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_for_bad_lifetime(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->url_signer->sign('/foo', -10);
    }

    /**
     * @test
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_for_empty_target(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->url_signer->sign('', 10);
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidArgument
     */
    public function test_exception_for_bad_max_usage(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->url_signer->sign('/foo', 10, 0);
    }

    /**
     * @test
     */
    public function test_exception_for_bad_path(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('/#f//sd$ is not a valid path.');

        $this->url_signer->sign('#f//sd$', 10);
    }

    /**
     * @test
     */
    public function a_singed_url_can_be_created_for_a_path(): void
    {
        $magic_link = $this->url_signer->sign('foo', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);

        $this->assertStringStartsWith('/foo', $magic_link->asString());
        $this->assertStringContainsString('expires=', $magic_link->asString());
        $this->assertStringContainsString('signature=', $magic_link->asString());
        $this->assertSame('/foo', $magic_link->protects());

        $this->assertSame($magic_link->asString(), (string)$magic_link);
    }

    /**
     * @test
     */
    public function two_signed_urls_are_not_the_same_for_the_same_target(): void
    {
        $signed1 = $this->url_signer->sign('foo', 10);
        $signed2 = $this->url_signer->sign('foo', 10);

        $this->assertNotSame($signed1, $signed2);
    }

    /**
     * @test
     */
    public function a_signed_url_can_be_created_for_a_full_url(): void
    {
        $magic_link = $this->url_signer->sign('https://foo.com/foo/', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);

        $this->assertStringStartsWith('https://foo.com/foo/', $magic_link->asString());
        $this->assertStringContainsString('expires=', $magic_link->asString());
        $this->assertStringContainsString('signature=', $magic_link->asString());
        $this->assertSame('https://foo.com/foo/', $magic_link->protects());
    }

    /**
     * @test
     */
    public function testCanBeCreatedForOnlySlash(): void
    {
        $magic_link = $this->url_signer->sign('/', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
    }

    /**
     * @test
     */
    public function testCanBeCreatedForOnlyHost(): void
    {
        $magic_link = $this->url_signer->sign('https://foo.com', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);

        $magic_link = $this->url_signer->sign('https://foo.com/', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
    }

    /**
     * @test
     */
    public function testCanBeCreatedWithExistingQueryParams(): void
    {
        $magic_link = $this->url_signer->sign('/web?foo=bar&baz=biz', 10);
        $this->assertStringStartsWith('/web?foo=bar&baz=biz', $magic_link->asString());
        $this->assertStringContainsString('expires=', $magic_link->asString());
        $this->assertStringContainsString('signature=', $magic_link->asString());
        $this->assertSame('/web?foo=bar&baz=biz', $magic_link->protects());
    }

    /**
     * @test
     */
    public function created_magic_links_are_stored(): void
    {
        $this->assertCount(0, $this->storage->all());
        $magic_link = $this->url_signer->sign('/web?foo=bar&baz=biz', 10);
        $all = $this->storage->all();
        $this->assertCount(1, $all);
        $this->assertArrayHasKey($magic_link->identifier(), $all);
    }

    /**
     * @test
     */
    public function garbage_collection_works(): void
    {
        $storage = new InMemoryStorage($test_clock = new TestClock());
        $signer = new UrlSigner($storage, new HMAC(Secret::generate()));

        $this->assertCount(0, $storage->all());
        $signer->sign('/web?foo=bar&baz=biz', 10);
        $signer->sign('/web?bar=bar&baz=biz', 10);
        $signer->sign('/web?baz=bar&baz=biz', 10);
        $this->assertCount(3, $storage->all());

        $test_clock->travelIntoFuture(11);

        GarbageCollector::clean($storage, 100);

        $this->assertCount(0, $storage->all());
    }

    /**
     * @test
     */
    public function max_usage_can_be_configured(): void
    {
        $link = $this->url_signer->sign('/foo', 10, 1);
        $this->assertSame(1, $link->maxUsage());

        $link = $this->url_signer->sign('/foo', 10, 10);
        $this->assertSame(10, $link->maxUsage());
    }

}