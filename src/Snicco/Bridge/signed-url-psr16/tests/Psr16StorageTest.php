<?php

declare(strict_types=1);

namespace Snicco\Bridge\SignedUrlPsr16\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Bridge\SignedUrlPsr16\Psr16Storage;
use Snicco\Component\SignedUrl\Exception\UnavailableStorage;
use Snicco\Component\SignedUrl\SignedUrl;
use Snicco\Component\SignedUrl\Storage\SignedUrlStorage;
use Snicco\Component\SignedUrl\Testing\SignedUrlStorageTests;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;

use function sleep;
use function time;

final class Psr16StorageTest extends TestCase
{
    use SignedUrlStorageTests;

    /**
     * @test
     */
    public function test_exception_if_link_cant_be_deleted_from_cache(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function delete($key)
            {
                return false;
            }
        };

        $storage = new Psr16Storage($cache);

        $url = SignedUrl::create('/foo', '/foo', 'id1', time() + 10, 2);

        $storage->store($url);

        $storage->consume('id1');

        $this->expectException(UnavailableStorage::class);
        $this->expectExceptionMessage('Could not delete signed url with id [id1] from the cache.');

        $storage->consume('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_link_cant_be_stored_in_cache(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function set($key, $value, $ttl = null)
            {
                return false;
            }
        };

        $storage = new Psr16Storage($cache);

        $url = SignedUrl::create('/foo', '/foo', 'id1', time() + 10, 2);

        $this->expectException(UnavailableStorage::class);
        $this->expectExceptionMessage("Could not save signed url for path [/foo].\nCache key: [signed_url_id1].");

        $storage->store($url);
    }

    /**
     * @test
     */
    public function test_exception_if_decremented_usage_cant_be_saved(): void
    {
        $cache = new class() extends ArrayCachePool {
            public bool $should_fail = false;

            public function set($key, $value, $ttl = null)
            {
                if ($this->should_fail) {
                    return false;
                }
                return parent::set($key, $value, $ttl);
            }
        };

        $storage = new Psr16Storage($cache);
        $url = SignedUrl::create('/foo', '/foo', 'id1', time() + 10, 2);

        $storage->store($url);

        $cache->should_fail = true;

        $this->expectException(UnavailableStorage::class);
        $this->expectExceptionMessage("Could not decrement usage for signed url.\nCache key: [signed_url_id1].");

        $storage->consume('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_data_is_corrupted(): void
    {
        $storage = new Psr16Storage($cache = new ArrayCachePool());
        $url = SignedUrl::create('/foo', '/foo', 'id1', time() + 10, 3);

        $storage->store($url);
        $storage->consume('id1');

        $cache->set('signed_url_id1', [
            'left_usages' => 1,
        ]);

        try {
            $storage->consume('id1');
            $this->fail('No exception thrown for bad cache content.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                "Cache content for signed url with cache key [signed_url_id1] are corrupted.\nMissing or invalid key [expires_at].",
                $e->getMessage()
            );
        }

        $cache->set('signed_url_id1', [
            'expires_at' => 1,
        ]);

        try {
            $storage->consume('id1');
            $this->fail('No exception thrown for bad cache content.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                "Cache content for signed url with cache key [signed_url_id1] are corrupted.\nMissing or invalid key [left_usages].",
                $e->getMessage()
            );
        }
    }

    /**
     * @param positive-int $seconds
     */
    protected function advanceTime(int $seconds, TestClock $clock): void
    {
        sleep($seconds);
    }

    protected function createStorage(Clock $clock): SignedUrlStorage
    {
        return new Psr16Storage(new ArrayCachePool());
    }
}
