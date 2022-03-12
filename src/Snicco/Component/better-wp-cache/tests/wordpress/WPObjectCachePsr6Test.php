<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use Psr\Cache\CacheItemInterface;
use Snicco\Component\BetterWPCache\CacheFactory;
use Snicco\Component\BetterWPCache\Exception\Psr6InvalidArgumentException;
use Snicco\Component\BetterWPCache\WPCacheAPI;
use Snicco\Component\BetterWPCache\WPObjectCachePsr6;
use stdClass;

use function restore_error_handler;
use function set_error_handler;

use const E_NOTICE;

/**
 * The test methods in this class are copied from
 * https://github.com/php-cache/integration-tests/blob/master/src/CachePoolTest.php.
 * We can't extend the provided test case because we already need to extend
 * WPTestCase.
 *
 * @see https://github.com/php-cache/integration-tests/issues/117
 *
 * @psalm-suppress InternalClass
 *
 * @internal
 */
final class WPObjectCachePsr6Test extends WPTestCase
{
    private WPObjectCachePsr6 $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = CacheFactory::psr6('testing');
    }

    /**
     * @test
     */
    public function test_delete_items_returns_false_if_one_item_cant_be_deleted(): void
    {
        $keys = ['key1', 'key2'];

        $cache_api = new class() extends WPCacheAPI {
            public function cacheDelete(string $key, string $group = ''): bool
            {
                if ('key2' === $key) {
                    return false;
                }

                return parent::cacheDelete($key, $group);
            }
        };
        $psr_cache = new WPObjectCachePsr6('testing', $cache_api);

        $item = $psr_cache->getItem('key1');
        $item->set('val1');

        $psr_cache->save($item);

        $item = $psr_cache->getItem('key2');
        $item->set('val2');

        $psr_cache->save($item);

        $this->assertFalse($psr_cache->deleteItems($keys));
    }

    /**
     * @test
     */
    public function test_commit_returns_false_if_one_item_cant_be_saved(): void
    {
        $cache_api = new class() extends WPCacheAPI {
            public function cacheSet(string $key, $data, string $group = '', int $expire = 0): bool
            {
                if ('key2' === $key) {
                    return false;
                }

                return parent::cacheSet($key, $data, $group, $expire);
            }
        };
        $psr_cache = new WPObjectCachePsr6('testing', $cache_api);

        $item = $psr_cache->getItem('key1');
        $item->set('val1');

        $psr_cache->saveDeferred($item);

        $item = $psr_cache->getItem('key2');
        $item->set('val2');

        $psr_cache->saveDeferred($item);

        $this->assertFalse($psr_cache->commit());
    }

    /**
     * @test
     */
    public function test_exception_for_empty_string_key(): void
    {
        $this->expectException(Psr6InvalidArgumentException::class);

        $this->cache->getItem('');
    }

    /**
     * @test
     */
    public function test_with_deferred_item_that_is_not_expired_is_cloned(): void
    {
        $item = $this->cache->getItem('key1');
        $item->set('val1');
        $item->expiresAfter(10);

        $this->cache->saveDeferred($item);

        $new = $this->cache->getItem('key1');
        $this->assertNotSame($item, $new);
    }

    /**
     * @test
     */
    public function test_exception_for_different_cache_item(): void
    {
        $item = new class() implements CacheItemInterface {
            public function getKey(): string
            {
                return '';
            }

            public function get(): string
            {
                return '';
            }

            public function isHit(): bool
            {
                return false;
            }

            public function set($value): self
            {
                return $this;
            }

            public function expiresAt($expiration): self
            {
                return $this;
            }

            public function expiresAfter($time): self
            {
                return $this;
            }
        };

        $this->expectException(Psr6InvalidArgumentException::class);
        $this->expectExceptionMessage('WPCacheItem');

        $this->cache->save($item);
    }

    /**
     * @test
     */
    public function test_is_always_miss_for_non_string_cache_data(): void
    {
        $cache_api = new class() extends WPCacheAPI {
            public function cacheGet(
                string $key,
                string $group = '',
                bool $force = false,
                bool &$found = null
            ): stdClass {
                return new stdClass();
            }
        };
        $psr_cache = new WPObjectCachePsr6('testing', $cache_api);

        $item = $psr_cache->getItem('key1');
        $item->set('val');

        $psr_cache->save($item);

        $new = $psr_cache->getItem('key1');
        $this->assertFalse($new->isHit());
        $this->assertNull($new->get());
    }

    /**
     * @test
     */
    public function test_is_always_miss_for_badly_serialized_data(): void
    {
        $notice_triggered = false;
        set_error_handler(function () use (&$notice_triggered): bool {
            $notice_triggered = true;

            return true;
        }, E_NOTICE);

        try {
            $cache_api = new class() extends WPCacheAPI {
                public function cacheGet(
                    string $key,
                    string $group = '',
                    bool $force = false,
                    bool &$found = null
                ): string {
                    $found = true;

                    return 'not-serialized';
                }
            };
            $psr_cache = new WPObjectCachePsr6('testing', $cache_api);

            $new = $psr_cache->getItem('key1');
            $this->assertFalse($new->isHit());
            $this->assertNull($new->get());

            $this->assertTrue($notice_triggered);
        } finally {
            restore_error_handler();
        }
    }

    /**
     * @test
     */
    public function test_different_groups_are_used(): void
    {
        $cache_one = CacheFactory::psr6('testing1');
        $cache_two = CacheFactory::psr6('testing2');

        $cache_one->save($cache_one->getItem('key1'));

        $this->assertFalse($cache_two->hasItem('key1'));
    }
}
