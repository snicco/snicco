<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use DateTime;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPCache\WPObjectCachePsr6;
use stdClass;
use WP_Object_Cache;

use function chr;
use function in_array;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_object;
use function is_string;
use function method_exists;

/**
 * @internal
 */
final class WPObjectCachePsr6IntegrationTest extends WPTestCase
{
    /**
     * @var array with functionName => reason
     */
    protected array $skippedTests = [];

    protected ?WPObjectCachePsr6 $cache = null;

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

    public function createCachePool(): WPObjectCachePsr6
    {
        return new WPObjectCachePsr6('testing');
    }

    /**
     * @before
     */
    public function setupService(): void
    {
        $this->cache = $this->createCachePool();
    }

    /**
     * @after
     */
    public function tearDownService(): void
    {
        if (null !== $this->cache) {
            $this->cache->clear();
        }
    }

    /**
     * Data provider for invalid keys.
     */
    public static function invalidKeys(): array
    {
        return [
            [true],
            [false],
            [null],
            [2],
            [2.5],
            ['{str'],
            ['rand{'],
            ['rand{str'],
            ['rand}str'],
            ['rand(str'],
            ['rand)str'],
            ['rand/str'],
            ['rand\\str'],
            ['rand@str'],
            ['rand:str'],
            [new stdClass()],
            [['array']],
        ];
    }

    /**
     * @test
     */
    public function basic_usage(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('4711');

        $this->cache->save($item);

        $item = $this->cache->getItem('key2');
        $item->set('4712');

        $this->cache->save($item);

        $fooItem = $this->cache->getItem('key');
        $this->assertTrue($fooItem->isHit());
        $this->assertEquals('4711', $fooItem->get());

        $barItem = $this->cache->getItem('key2');
        $this->assertTrue($barItem->isHit());
        $this->assertEquals('4712', $barItem->get());

        // Remove 'key' and make sure 'key2' is still there
        $this->cache->deleteItem('key');
        $this->assertFalse($this->cache->getItem('key')->isHit());
        $this->assertTrue($this->cache->getItem('key2')->isHit());

        // Remove everything
        $this->cache->clear();
        $this->assertFalse($this->cache->getItem('key')->isHit());
        $this->assertFalse($this->cache->getItem('key2')->isHit());
    }

    /**
     * @test
     */
    public function basic_usage_with_long_key(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $pool = $this->createCachePool();

        $key = str_repeat('a', 300);

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
        $this->assertSame($key, $item->getKey());

        $item->set('value');
        $this->assertTrue($pool->save($item));

        $item = $pool->getItem($key);
        $this->assertTrue($item->isHit());
        $this->assertSame($key, $item->getKey());
        $this->assertSame('value', $item->get());

        $this->assertTrue($pool->deleteItem($key));

        $item = $pool->getItem($key);
        $this->assertFalse($item->isHit());
    }

    /**
     * @test
     */
    public function item_modifiers_returns_static(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $this->assertSame($item, $item->set('4711'));
        $this->assertSame($item, $item->expiresAfter(2));
        $this->assertSame($item, $item->expiresAt(new DateTime('+2hours')));
    }

    /**
     * @test
     */
    public function get_item(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->save($item);

        // get existing item
        $item = $this->cache->getItem('key');
        $this->assertEquals('value', $item->get(), 'A stored item must be returned from cached.');
        $this->assertEquals('key', $item->getKey(), 'Cache key can not change.');

        // get non-existent item
        $item = $this->cache->getItem('key2');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit is false.");
    }

    /**
     * @test
     */
    public function get_items(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $keys = ['foo', 'bar', 'baz'];
        $items = $this->cache->getItems($keys);

        $count = 0;

        /** @var CacheItemInterface $item */
        foreach ($items as $i => $item) {
            $item->set($i);
            $this->cache->save($item);

            ++$count;
        }

        $this->assertSame(3, $count);

        $keys[] = 'biz';
        /** @var CacheItemInterface[] $items */
        $items = $this->cache->getItems($keys);
        $count = 0;
        foreach ($items as $key => $item) {
            $itemKey = $item->getKey();
            $this->assertEquals($itemKey, $key, 'Keys must be preserved when fetching multiple items');
            $this->assertEquals('biz' !== $key, $item->isHit());
            $this->assertTrue(in_array($key, $keys, true), 'Cache key can not change.');

            // Remove $key for $keys
            foreach ($keys as $k => $v) {
                if ($v === $key) {
                    unset($keys[$k]);
                }
            }

            ++$count;
        }

        $this->assertSame(4, $count);
    }

    /**
     * @test
     */
    public function get_items_empty(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $items = $this->cache->getItems([]);
        $this->assertTrue(
            is_iterable($items),
            'A call to getItems with an empty array must always return an array or \Traversable.'
        );

        $count = 0;
        foreach ($items as $item) {
            ++$count;
        }

        $this->assertSame(0, $count);
    }

    /**
     * @test
     */
    public function has_item(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->save($item);

        // has existing item
        $this->assertTrue($this->cache->hasItem('key'));

        // has non-existent item
        $this->assertFalse($this->cache->hasItem('key2'));
    }

    /**
     * @test
     */
    public function clear(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->save($item);

        $return = $this->cache->clear();

        $this->assertTrue($return, 'clear() must return true if cache was cleared. ');
        $this->assertFalse(
            $this->cache->getItem('key')
                ->isHit(),
            'No item should be a hit after the cache is cleared. '
        );
        $this->assertFalse($this->cache->hasItem('key2'), 'The cache pool should be empty after it is cleared.');
    }

    /**
     * @test
     */
    public function clear_with_deferred_items(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->saveDeferred($item);

        $this->cache->clear();
        $this->cache->commit();

        $this->assertFalse($this->cache->getItem('key')->isHit(), 'Deferred items must be cleared on clear(). ');
    }

    /**
     * @test
     */
    public function delete_item(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->save($item);

        $this->assertTrue($this->cache->deleteItem('key'));
        $this->assertFalse($this->cache->getItem('key')->isHit(), 'A deleted item should not be a hit.');
        $this->assertFalse($this->cache->hasItem('key'), 'A deleted item should not be a in cache.');

        $this->assertTrue($this->cache->deleteItem('key2'), 'Deleting an item that does not exist should return true.');
    }

    /**
     * @test
     */
    public function delete_items(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $items = $this->cache->getItems(['foo', 'bar', 'baz']);

        /** @var CacheItemInterface $item */
        foreach ($items as $idx => $item) {
            $item->set($idx);
            $this->cache->save($item);
        }

        // All should be a hit but 'biz'
        $this->assertTrue($this->cache->getItem('foo')->isHit());
        $this->assertTrue($this->cache->getItem('bar')->isHit());
        $this->assertTrue($this->cache->getItem('baz')->isHit());
        $this->assertFalse($this->cache->getItem('biz')->isHit());

        $return = $this->cache->deleteItems(['foo', 'bar', 'biz']);
        $this->assertTrue($return);

        $this->assertFalse($this->cache->getItem('foo')->isHit());
        $this->assertFalse($this->cache->getItem('bar')->isHit());
        $this->assertTrue($this->cache->getItem('baz')->isHit());
        $this->assertFalse($this->cache->getItem('biz')->isHit());
    }

    /**
     * @test
     */
    public function save(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $return = $this->cache->save($item);

        $this->assertTrue($return, 'save() should return true when items are saved.');
        $this->assertEquals('value', $this->cache->getItem('key')->get());
    }

    /**
     * @test
     */
    public function save_expired(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->expiresAt(DateTime::createFromFormat('U', (string) (time() + 10)));

        $this->cache->save($item);
        $item->expiresAt(DateTime::createFromFormat('U', (string) (time() - 1)));
        $this->cache->save($item);
        $item = $this->cache->getItem('key');
        $this->assertFalse($item->isHit(), 'Cache should not save expired items');
    }

    /**
     * @test
     */
    public function save_without_expire(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('test_ttl_null');
        $item->set('data');

        $this->cache->save($item);

        // Use a new pool instance to ensure that we don't hit any caches
        $pool = $this->createCachePool();
        $item = $pool->getItem('test_ttl_null');

        $this->assertTrue($item->isHit(), 'Cache should have retrieved the items');
        $this->assertEquals('data', $item->get());
    }

    /**
     * @test
     */
    public function deferred_save(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('4711');

        $return = $this->cache->saveDeferred($item);
        $this->assertTrue($return, 'save() should return true when items are saved.');

        $item = $this->cache->getItem('key2');
        $item->set('4712');

        $this->cache->saveDeferred($item);

        // They are not saved yet but should be a hit
        $this->assertTrue(
            $this->cache->hasItem('key'),
            'Deferred items should be considered as a part of the cache even before they are committed'
        );
        $this->assertTrue(
            $this->cache->getItem('key')
                ->isHit(),
            'Deferred items should be a hit even before they are committed'
        );
        $this->assertTrue($this->cache->getItem('key2')->isHit());

        $this->cache->commit();

        // They should be a hit after the commit as well
        $this->assertTrue($this->cache->getItem('key')->isHit());
        $this->assertTrue($this->cache->getItem('key2')->isHit());
    }

    /**
     * @test
     */
    public function deferred_expired(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('4711');
        $item->expiresAt(DateTime::createFromFormat('U', (string) (time() - 1)));

        $this->cache->saveDeferred($item);

        $this->assertFalse($this->cache->hasItem('key'), 'Cache should not have expired deferred item');
        $this->cache->commit();
        $item = $this->cache->getItem('key');
        $this->assertFalse($item->isHit(), 'Cache should not save expired items');
    }

    /**
     * @test
     */
    public function delete_deferred_item(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('4711');

        $this->cache->saveDeferred($item);
        $this->assertTrue($this->cache->getItem('key')->isHit());

        $this->cache->deleteItem('key');
        $this->assertFalse(
            $this->cache->hasItem('key'),
            'You must be able to delete a deferred item before committed. '
        );
        $this->assertFalse(
            $this->cache->getItem('key')
                ->isHit(),
            'You must be able to delete a deferred item before committed. '
        );

        $this->cache->commit();
        $this->assertFalse($this->cache->hasItem('key'), 'A deleted item should not reappear after commit. ');
        $this->assertFalse($this->cache->getItem('key')->isHit(), 'A deleted item should not reappear after commit. ');
    }

    /**
     * @test
     */
    public function deferred_save_without_commit(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->prepareDeferredSaveWithoutCommit();
        gc_collect_cycles();

        $cache = $this->createCachePool();
        $this->assertTrue(
            $cache->getItem('key')
                ->isHit(),
            'A deferred item should automatically be committed on CachePool::__destruct().'
        );
    }

    /**
     * @test
     */
    public function commit(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->saveDeferred($item);
        $return = $this->cache->commit();

        $this->assertTrue($return, 'commit() should return true on successful commit. ');
        $this->assertEquals('value', $this->cache->getItem('key')->get());

        $return = $this->cache->commit();
        $this->assertTrue($return, 'commit() should return true even if no items were deferred. ');
    }

    /**
     * @medium
     *
     * @test
     */
    public function expiration(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->expiresAfter(2);

        $this->cache->save($item);

        sleep(3);
        $item = $this->cache->getItem('key');
        $this->assertFalse($item->isHit());
        $this->assertNull($item->get(), "Item's value must be null when isHit() is false.");
    }

    /**
     * @test
     */
    public function expires_at(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->expiresAt(new DateTime('+2hours'));

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }

    /**
     * @test
     */
    public function expires_at_with_null(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->expiresAt(null);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }

    /**
     * @test
     */
    public function expires_after_with_null(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->expiresAfter(null);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }

    /**
     * @test
     */
    public function key_length(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $key = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789_.';
        $item = $this->cache->getItem($key);
        $item->set('value');
        $this->assertTrue($this->cache->save($item), 'The implementation does not support a valid cache key');

        $this->assertTrue($this->cache->hasItem($key));
    }

    /**
     * @dataProvider invalidKeys
     *
     * @param mixed $key
     *
     * @test
     */
    public function get_item_invalid_keys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->cache->getItem($key);
    }

    /**
     * @dataProvider invalidKeys
     *
     * @param mixed $key
     *
     * @test
     */
    public function get_items_invalid_keys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->cache->getItems(['key1', $key, 'key2']);
    }

    /**
     * @dataProvider invalidKeys
     *
     * @param mixed $key
     *
     * @test
     */
    public function has_item_invalid_keys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->cache->hasItem($key);
    }

    /**
     * @dataProvider invalidKeys
     *
     * @param mixed $key
     *
     * @test
     */
    public function delete_item_invalid_keys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->cache->deleteItem($key);
    }

    /**
     * @dataProvider invalidKeys
     *
     * @param mixed $key
     *
     * @test
     */
    public function delete_items_invalid_keys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->cache->deleteItems(['key1', $key, 'key2']);
    }

    /**
     * @test
     */
    public function data_type_string(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('5');

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue('5' === $item->get(), 'Wrong data type. If we store a string we must get an string back.');
        $this->assertTrue(is_string($item->get()), 'Wrong data type. If we store a string we must get an string back.');
    }

    /**
     * @test
     */
    public function data_type_integer(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set(5);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(5 === $item->get(), 'Wrong data type. If we store an int we must get an int back.');
        $this->assertTrue(is_int($item->get()), 'Wrong data type. If we store an int we must get an int back.');
    }

    /**
     * @test
     */
    public function data_type_null(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set(null);

        $this->cache->save($item);

        $this->assertTrue(
            $this->cache->hasItem('key'),
            'Null is a perfectly fine cache value. hasItem() should return true when null are stored. '
        );
        $item = $this->cache->getItem('key');
        $this->assertTrue(null === $item->get(), 'Wrong data type. If we store null we must get an null back.');
        $this->assertTrue(null === $item->get(), 'Wrong data type. If we store null we must get an null back.');
        $this->assertTrue($item->isHit(), 'isHit() should return true when null are stored. ');
    }

    /**
     * @test
     */
    public function data_type_float(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $float = 1.23456789;
        $item = $this->cache->getItem('key');
        $item->set($float);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(is_float($item->get()), 'Wrong data type. If we store float we must get an float back.');
        $this->assertEquals($float, $item->get());
        $this->assertTrue($item->isHit(), 'isHit() should return true when float are stored. ');
    }

    /**
     * @test
     */
    public function data_type_boolean(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set(true);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(is_bool($item->get()), 'Wrong data type. If we store boolean we must get an boolean back.');
        $this->assertTrue($item->get());
        $this->assertTrue($item->isHit(), 'isHit() should return true when true are stored. ');

        $item = $this->cache->getItem('key2');
        $item->set(false);

        $this->cache->save($item);

        $item = $this->cache->getItem('key2');
        $this->assertTrue(is_bool($item->get()), 'Wrong data type. If we store boolean we must get an boolean back.');
        $this->assertFalse($item->get());
        $this->assertTrue($item->isHit(), 'isHit() should return true when false is stored. ');
    }

    /**
     * @test
     */
    public function data_type_array(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $array = [
            'a' => 'foo',
            2 => 'bar',
        ];
        $item = $this->cache->getItem('key');
        $item->set($array);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(is_array($item->get()), 'Wrong data type. If we store array we must get an array back.');
        $this->assertEquals($array, $item->get());
        $this->assertTrue($item->isHit(), 'isHit() should return true when array are stored. ');
    }

    /**
     * @test
     */
    public function data_type_object(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $object = new stdClass();
        $object->a = 'foo';
        $item = $this->cache->getItem('key');
        $item->set($object);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue(is_object($item->get()), 'Wrong data type. If we store object we must get an object back.');
        $this->assertEquals($object, $item->get());
        $this->assertTrue($item->isHit(), 'isHit() should return true when object are stored. ');
    }

    /**
     * @test
     */
    public function binary_data(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $data = '';
        for ($i = 0; $i < 256; ++$i) {
            $data .= chr($i);
        }

        $item = $this->cache->getItem('key');
        $item->set($data);

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue($data === $item->get(), 'Binary data must survive a round trip.');
    }

    /**
     * @test
     */
    public function is_hit(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }

    /**
     * @test
     */
    public function is_hit_deferred(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->saveDeferred($item);

        // Test accessing the value before it is committed
        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());

        $this->cache->commit();
        $item = $this->cache->getItem('key');
        $this->assertTrue($item->isHit());
    }

    /**
     * @test
     */
    public function save_deferred_when_changing_values(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->saveDeferred($item);

        $item = $this->cache->getItem('key');
        $item->set('new value');

        $item = $this->cache->getItem('key');
        $this->assertEquals(
            'value',
            $item->get(),
            'Items that is put in the deferred queue should not get their values changed'
        );

        $this->cache->commit();
        $item = $this->cache->getItem('key');
        $this->assertEquals(
            'value',
            $item->get(),
            'Items that is put in the deferred queue should not get their values changed'
        );
    }

    /**
     * @test
     */
    public function save_deferred_overwrite(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');

        $this->cache->saveDeferred($item);

        $item = $this->cache->getItem('key');
        $item->set('new value');

        $this->cache->saveDeferred($item);

        $item = $this->cache->getItem('key');
        $this->assertEquals('new value', $item->get());

        $this->cache->commit();
        $item = $this->cache->getItem('key');
        $this->assertEquals('new value', $item->get());
    }

    /**
     * @test
     */
    public function saving_object(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set(new DateTime());

        $this->cache->save($item);

        $item = $this->cache->getItem('key');
        $value = $item->get();
        $this->assertInstanceOf(DateTime::class, $value, 'You must be able to store objects in cache.');
    }

    /**
     * @medium
     *
     * @test
     */
    public function has_item_returns_false_when_deferred_item_is_expired(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $item = $this->cache->getItem('key');
        $item->set('value');
        $item->expiresAfter(2);

        $this->cache->saveDeferred($item);

        sleep(3);
        $this->assertFalse($this->cache->hasItem('key'));
    }

    private function prepareDeferredSaveWithoutCommit(): void
    {
        $cache = $this->cache;
        $this->cache = null;

        $item = $cache->getItem('key');
        $item->set('4711');

        $cache->saveDeferred($item);
    }
}
