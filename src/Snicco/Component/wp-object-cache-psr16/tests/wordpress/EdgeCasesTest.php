<?php

declare(strict_types=1);


namespace Snicco\Component\WPObjectCachePsr16\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use InvalidArgumentException;
use RuntimeException;
use Snicco\Component\WPObjectCachePsr16\ScopableWP;
use Snicco\Component\WPObjectCachePsr16\WPObjectCachePsr16;

final class EdgeCasesTest extends WPTestCase
{
    private WPObjectCachePsr16 $cache;
    private ScopableWP $wp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new WPObjectCachePsr16($this->wp = new ScopableWP());
    }

    /**
     * @test
     */
    public function test_invalid_serialized_data(): void
    {
        $this->cache->set('foo', 'bar');
        $this->assertSame('bar', $this->cache->get('foo'));

        // insert bad cache data
        $this->wp->cacheSet('foo', 'bar_not_serialized');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            "Cant unserialize cache content for key [foo].\nValue [bar_not_serialized] is corrupted."
        );

        $this->cache->get('foo');
    }

    /**
     * @test
     */
    public function boolean_false_can_be_saved_in_cache(): void
    {
        $this->cache->set('foo', false);
        $this->assertSame(false, $this->cache->get('foo'));
    }

    /**
     * @test
     */
    public function test_invalid_non_string_cache_content(): void
    {
        $this->cache->set('foo', ['bar']);
        $this->assertSame(['bar'], $this->cache->get('foo'));

        // insert bad cache data
        $this->wp->cacheSet('foo', ['bar']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cache content for key [foo] was not a serialized string.');

        $this->cache->get('foo');
    }

    /**
     * @test
     *
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exception_for_invalid_ttl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$ttl must be null,integer or DateInterval. Got [string].');

        $this->cache->set('foo', 'bar', '123435');
    }

}