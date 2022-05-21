<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlWP\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use RuntimeException;
use Snicco\Bridge\SignedUrlWP\CacheAPI;
use Snicco\Bridge\SignedUrlWP\WPObjectCacheSignedUrlStorage;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;
use WP_Object_Cache;

use function method_exists;
use function sleep;
use function time;
use function wp_cache_flush;

/**
 * @internal
 */
final class WPObjectCacheSignedUrlStorageTest extends WPTestCase
{
    use SignedUrlStorageTests;

    protected function setUp(): void
    {
        parent::setUp();
        global $wp_object_cache;

        if (! $wp_object_cache instanceof WP_Object_Cache) {
            throw new RuntimeException('wp object cache not setup.');
        }

        if (! method_exists($wp_object_cache, 'redis_status')) {
            throw new RuntimeException('wp object cache does not have method redis_status');
        }

        if (false === $wp_object_cache->redis_status()) {
            throw new RuntimeException('Redis not running.');
        }
    }

    protected function tearDown(): void
    {
        wp_cache_flush();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_cache_delete_returns_false(): void
    {
        $cache = new class() extends CacheAPI {
            public function cacheDelete(string $key, string $group = ''): bool
            {
                return false;
            }
        };

        $url = SignedUrl::create('/foo', '/foo', 'foo_sig', time() + 1, 1);

        $storage = new WPObjectCacheSignedUrlStorage('prefix', $cache);

        $storage->store($url);

        $this->expectException(UnavailableStorage::class);
        $this->expectExceptionMessage('Signed url [foo_sig] could not be deleted from the WP object cache');

        $storage->consume('foo_sig');
    }

    /**
     * @test
     */
    public function that_an_exception_is_thrown_if_cache_set_returns_false(): void
    {
        $cache = new class() extends CacheAPI {
            public function cacheSet(string $key, $data, string $group = '', int $expire = 0): bool
            {
                return false;
            }
        };

        $url = SignedUrl::create('/foo', '/foo', 'foo_sig', time() + 1, 1);

        $storage = new WPObjectCacheSignedUrlStorage('prefix', $cache);

        $this->expectException(UnavailableStorage::class);
        $this->expectExceptionMessage('Singed url for protected path [/foo] could not be stored.');

        $storage->store($url);
    }

    protected function createStorage(Clock $clock): SignedUrlStorage
    {
        return new WPObjectCacheSignedUrlStorage('prefix');
    }

    protected function advanceTime(int $seconds, TestClock $clock): void
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        sleep($seconds);
    }
}
