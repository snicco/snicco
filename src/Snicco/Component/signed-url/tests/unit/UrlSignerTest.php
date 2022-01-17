<?php

declare(strict_types=1);

namespace Tests\SignedUrl\unit;

use LogicException;
use Snicco\SignedUrl\Secret;
use InvalidArgumentException;
use Snicco\SignedUrl\SignedUrl;
use Snicco\SignedUrl\UrlSigner;
use Snicco\SignedUrl\Sha256Hasher;
use Tests\Codeception\shared\UnitTest;
use Snicco\SignedUrl\GarbageCollector;
use Tests\Codeception\shared\TestClock;
use Snicco\SignedUrl\Storage\InMemoryStorage;

final class UrlSignerTest extends UnitTest
{
    
    /**
     * @var UrlSigner
     */
    private $url_signer;
    
    /**
     * @var InMemoryStorage
     */
    private $storage;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->storage = new InMemoryStorage();
        $this->url_signer =
            new UrlSigner($this->storage, new Sha256Hasher(Secret::generate()), 0);
    }
    
    /** @test */
    public function an_exception_is_thrown_if_the_path_contains_the_expired_query_param()
    {
        $this->expectException(LogicException::class);
        $this->url_signer->sign('/foo?expires=100', 10);
    }
    
    /** @test */
    public function an_exception_is_thrown_if_the_path_contains_the_signature_query_param()
    {
        $this->expectException(LogicException::class);
        $this->url_signer->sign('/foo?signature=100', 10);
    }
    
    /** @test */
    public function test_exception_for_bad_lifetime()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->url_signer->sign('/foo', -10);
    }
    
    /** @test */
    public function test_exception_for_empty_target()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->url_signer->sign('', 10);
    }
    
    /** @test */
    public function test_exception_for_bad_max_usage()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->url_signer->sign('/foo', 10, 0);
    }
    
    /** @test */
    public function testExceptionForBadPath()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('/#f//sd$ is not a valid path.');
    
        $this->url_signer->sign('#f//sd$', 10);
    }
    
    /** @test */
    public function a_magic_link_can_be_created_for_a_path()
    {
        $magic_link = $this->url_signer->sign('foo', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
        
        $this->assertStringStartsWith('/foo', $magic_link->asString());
        $this->assertStringContainsString('expires=', $magic_link->asString());
        $this->assertStringContainsString('signature=', $magic_link->asString());
        $this->assertSame('/foo', $magic_link->protects());
    }
    
    /** @test */
    public function a_magic_link_can_be_created_for_a_full_url()
    {
        $magic_link = $this->url_signer->sign('https://foo.com/foo/', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
        
        $this->assertStringStartsWith('https://foo.com/foo/', $magic_link->asString());
        $this->assertStringContainsString('expires=', $magic_link->asString());
        $this->assertStringContainsString('signature=', $magic_link->asString());
        $this->assertSame('https://foo.com/foo/', $magic_link->protects());
    }
    
    /** @test */
    public function testCanBeCreatedForOnlySlash()
    {
        $magic_link = $this->url_signer->sign('/', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
    }
    
    /** @test */
    public function testCanBeCreatedForOnlyHost()
    {
        $magic_link = $this->url_signer->sign('https://foo.com', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
    
        $magic_link = $this->url_signer->sign('https://foo.com/', 10);
        $this->assertInstanceOf(SignedUrl::class, $magic_link);
    }
    
    /** @test */
    public function testCanBeCreatedWithExistingQueryParams()
    {
        $magic_link = $this->url_signer->sign('/web?foo=bar&baz=biz', 10);
        $this->assertStringStartsWith('/web?foo=bar&baz=biz', $magic_link->asString());
        $this->assertStringContainsString('expires=', $magic_link->asString());
        $this->assertStringContainsString('signature=', $magic_link->asString());
        $this->assertSame('/web?foo=bar&baz=biz', $magic_link->protects());
    }
    
    /** @test */
    public function created_magic_links_are_stored()
    {
        $this->assertCount(0, $this->storage->all());
        $magic_link = $this->url_signer->sign('/web?foo=bar&baz=biz', 10);
        $all = $this->storage->all();
        $this->assertCount(1, $all);
        $this->assertArrayHasKey($magic_link->identifier(), $all);
    }
    
    /** @test */
    public function garbage_collection_works()
    {
        $storage = new InMemoryStorage($test_clock = new TestClock());
        $signer = new UrlSigner($storage, new Sha256Hasher(Secret::generate()));
    
        $this->assertCount(0, $storage->all());
        $signer->sign('/web?foo=bar&baz=biz', 10);
        $signer->sign('/web?bar=bar&baz=biz', 10);
        $signer->sign('/web?baz=bar&baz=biz', 10);
        $this->assertCount(3, $storage->all());
    
        $test_clock->travelIntoFuture(11);
    
        GarbageCollector::clean($storage, 100);
    
        $this->assertCount(0, $storage->all());
    }
    
    /** @test */
    public function max_usage_can_be_configured()
    {
        $link = $this->url_signer->sign('/foo', 10, 1);
        $this->assertSame(1, $link->maxUsage());
    
        $link = $this->url_signer->sign('/foo', 10, 10);
        $this->assertSame(10, $link->maxUsage());
    }
    
}