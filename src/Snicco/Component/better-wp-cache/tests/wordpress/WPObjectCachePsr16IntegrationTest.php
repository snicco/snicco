<?php

declare(strict_types=1);

namespace Snicco\Component\BetterWPCache\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use DateInterval;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use RuntimeException;
use Snicco\Component\BetterWPCache\CacheFactory;
use stdClass;
use WP_Object_Cache;

use function method_exists;
use function sprintf;

/**
 * The test methods in this class are copied from
 * https://github.com/php-cache/integration-tests/blob/master/src/SimpleCacheTest.php. We can't
 * extend the provided test case because we already need to extend WPTestCase.
 *
 * @see https://github.com/php-cache/integration-tests/issues/117
 */
final class WPObjectCachePsr16IntegrationTest extends WPTestCase
{
    private CacheInterface $cache;

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

    /**
     * Data provider for invalid cache keys.
     *
     * @return array
     */
    public static function invalidKeys()
    {
        return array_merge(
            self::invalidArrayKeys(),
            [
                [2],
            ]
        );
    }

    /**
     * Data provider for invalid array keys.
     *
     * @return array
     */
    public static function invalidArrayKeys()
    {
        return [
            [''],
            [true],
            [false],
            [null],
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
     * @return array
     */
    public static function invalidTtl()
    {
        return [
            [''],
            [true],
            [false],
            ['abc'],
            [2.5],
            [' 1'],    // can be casted to a int
            ['12foo'], // can be casted to a int
            ['025'],   // can be interpreted as hex
            [new stdClass()],
            [['array']],
        ];
    }

    /**
     * Data provider for valid keys.
     *
     * @return array
     */
    public static function validKeys()
    {
        return [
            ['AbC19_.'],
            ['1234567890123456789012345678901234567890123456789012345678901234'],
        ];
    }

    /**
     * Data provider for valid data to store.
     *
     * @return array
     */
    public static function validData()
    {
        return [
            ['AbC19_.'],
            [4711],
            [47.11],
            [true],
            [null],
            [[
                'key' => 'value',
            ]],
            [new stdClass()],
        ];
    }

    /**
     * @before
     */
    public function setupService(): void
    {
        $this->cache = $this->createSimpleCache();
    }

    /**
     * @return CacheInterface that is used in the tests
     */
    public function createSimpleCache()
    {
        return CacheFactory::psr16('testing');
    }

    /**
     * @after
     */
    public function tearDownService(): void
    {
        if ($this->cache !== null) {
            $this->cache->clear();
        }
    }

    public function testSet(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->set('key', 'value');
        $this->assertTrue($result, 'set() must return true if success');
        $this->assertEquals('value', $this->cache->get('key'));
    }

    /**
     * @medium
     */
    public function testSetTtl(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->set('key1', 'value', 2);
        $this->assertTrue($result, 'set() must return true if success');
        $this->assertEquals('value', $this->cache->get('key1'));

        $this->cache->set('key2', 'value', new DateInterval('PT2S'));
        $this->assertEquals('value', $this->cache->get('key2'));

        $this->advanceTime(3);

        $this->assertNull($this->cache->get('key1'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
    }

    /**
     * Advance time perceived by the cache for the purposes of testing TTL.
     * The default implementation sleeps for the specified duration,
     * but subclasses are encouraged to override this,
     * adjusting a mocked time possibly set up in {@link createSimpleCache()},
     * to speed up the tests.
     *
     * @param int $seconds
     */
    public function advanceTime($seconds): void
    {
        sleep($seconds);
    }

    public function testSetExpiredTtl(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key0', 'value');
        $this->cache->set('key0', 'value', 0);
        $this->assertNull($this->cache->get('key0'));
        $this->assertFalse($this->cache->has('key0'));

        $this->cache->set('key1', 'value', -1);
        $this->assertNull($this->cache->get('key1'));
        $this->assertFalse($this->cache->has('key1'));
    }

    public function testGet(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertNull($this->cache->get('key'));
        $this->assertEquals('foo', $this->cache->get('key', 'foo'));

        $this->cache->set('key', 'value');
        $this->assertEquals('value', $this->cache->get('key', 'foo'));
    }

    public function testDelete(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue(
            $this->cache->delete('key'),
            'Deleting a value that does not exist should return true'
        );
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->delete('key'), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key'), 'Values must be deleted on delete()');
    }

    public function testClear(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue($this->cache->clear(), 'Clearing an empty cache should return true');
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->clear(), 'Delete must return true on success');
        $this->assertNull($this->cache->get('key'), 'Values must be deleted on clear()');
    }

    public function testSetMultiple(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple([
            'key0' => 'value0',
            'key1' => 'value1',
        ]);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testSetMultipleWithIntegerArrayKey(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->setMultiple([
            '0' => 'value0',
        ]);
        $this->assertTrue($result, 'setMultiple() must return true if success');
        $this->assertEquals('value0', $this->cache->get('0'));
    }

    /**
     * @medium
     */
    public function testSetMultipleTtl(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple([
            'key2' => 'value2',
            'key3' => 'value3',
        ], 2);
        $this->assertEquals('value2', $this->cache->get('key2'));
        $this->assertEquals('value3', $this->cache->get('key3'));

        $this->cache->setMultiple([
            'key4' => 'value4',
        ], new DateInterval('PT2S'));
        $this->assertEquals('value4', $this->cache->get('key4'));

        $this->advanceTime(3);
        $this->assertNull($this->cache->get('key2'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key3'), 'Value must expire after ttl.');
        $this->assertNull($this->cache->get('key4'), 'Value must expire after ttl.');
    }

    public function testSetMultipleExpiredTtl(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple([
            'key0' => 'value0',
            'key1' => 'value1',
        ], 0);
        $this->assertNull($this->cache->get('key0'));
        $this->assertNull($this->cache->get('key1'));
    }

    public function testSetMultipleWithGenerator(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 'key0' => 'value0';
            yield 'key1' => 'value1';
        };

        $this->cache->setMultiple($gen());
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertEquals('value1', $this->cache->get('key1'));
    }

    public function testGetMultiple(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $result = $this->cache->getMultiple(['key0', 'key1']);
        $keys = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertNull($r);
        }
        sort($keys);
        $this->assertSame(['key0', 'key1'], $keys);

        $this->cache->set('key3', 'value');
        $result = $this->cache->getMultiple(['key2', 'key3', 'key4'], 'foo');
        $keys = [];
        foreach ($result as $key => $r) {
            $keys[] = $key;
            if ($key === 'key3') {
                $this->assertEquals('value', $r);
            } else {
                $this->assertEquals('foo', $r);
            }
        }
        sort($keys);
        $this->assertSame(['key2', 'key3', 'key4'], $keys);
    }

    public function testGetMultipleWithGenerator(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 1 => 'key0';
            yield 1 => 'key1';
        };

        $this->cache->set('key0', 'value0');
        $result = $this->cache->getMultiple($gen());
        $keys = [];
        foreach ($result as $key => $r) {
            $keys[] = $key;
            if ($key === 'key0') {
                $this->assertEquals('value0', $r);
            } elseif ($key === 'key1') {
                $this->assertNull($r);
            } else {
                $this->assertFalse(true, 'This should not happend');
            }
        }
        sort($keys);
        $this->assertSame(['key0', 'key1'], $keys);
        $this->assertEquals('value0', $this->cache->get('key0'));
        $this->assertNull($this->cache->get('key1'));
    }

    public function testDeleteMultiple(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertTrue(
            $this->cache->deleteMultiple([]),
            'Deleting a empty array should return true'
        );
        $this->assertTrue(
            $this->cache->deleteMultiple(['key']),
            'Deleting a value that does not exist should return true'
        );

        $this->cache->set('key0', 'value0');
        $this->cache->set('key1', 'value1');
        $this->assertTrue(
            $this->cache->deleteMultiple(['key0', 'key1']),
            'Delete must return true on success'
        );
        $this->assertNull($this->cache->get('key0'), 'Values must be deleted on deleteMultiple()');
        $this->assertNull($this->cache->get('key1'), 'Values must be deleted on deleteMultiple()');
    }

    public function testDeleteMultipleGenerator(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $gen = function () {
            yield 1 => 'key0';
            yield 1 => 'key1';
        };
        $this->cache->set('key0', 'value0');
        $this->assertTrue(
            $this->cache->deleteMultiple($gen()),
            'Deleting a generator should return true'
        );

        $this->assertNull($this->cache->get('key0'), 'Values must be deleted on deleteMultiple()');
        $this->assertNull($this->cache->get('key1'), 'Values must be deleted on deleteMultiple()');
    }

    public function testHas(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->assertFalse($this->cache->has('key0'));
        $this->cache->set('key0', 'value0');
        $this->assertTrue($this->cache->has('key0'));
    }

    public function testBasicUsageWithLongKey(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $key = str_repeat('a', 300);

        $this->assertFalse($this->cache->has($key));
        $this->assertTrue($this->cache->set($key, 'value'));

        $this->assertTrue($this->cache->has($key));
        $this->assertSame('value', $this->cache->get($key));

        $this->assertTrue($this->cache->delete($key));

        $this->assertFalse($this->cache->has($key));
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testGetInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->get($key);
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testGetMultipleInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->getMultiple(['key1', $key, 'key2']);
    }

    public function testGetMultipleNoIterable(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->getMultiple('key');
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testSetInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        try {
            $this->cache->set($key, 'foobar');
            $this->fail(sprintf('No expection was thrown for key [%s]', $key));
        } catch (InvalidArgumentException $e) {
            $this->assertTrue(true);
        }
    }

    /**
     * @dataProvider invalidArrayKeys
     */
    public function testSetMultipleInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $values = function () use ($key) {
            yield 'key1' => 'foo';
            yield $key => 'bar';
            yield 'key2' => 'baz';
        };
        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->setMultiple($values());
    }

    public function testSetMultipleNoIterable(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->setMultiple('key');
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testHasInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->has($key);
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testDeleteInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->delete($key);
    }

    /**
     * @dataProvider invalidKeys
     */
    public function testDeleteMultipleInvalidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->deleteMultiple(['key1', $key, 'key2']);
    }

    public function testDeleteMultipleNoIterable(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->deleteMultiple('key');
    }

    /**
     * @dataProvider invalidTtl
     */
    public function testSetInvalidTtl($ttl): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->set('key', 'value', $ttl);
    }

    /**
     * @dataProvider invalidTtl
     */
    public function testSetMultipleInvalidTtl($ttl): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->expectException('Psr\SimpleCache\InvalidArgumentException');
        $this->cache->setMultiple([
            'key' => 'value',
        ], $ttl);
    }

    public function testNullOverwrite(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 5);
        $this->cache->set('key', null);

        $this->assertNull(
            $this->cache->get('key'),
            'Setting null to a key must overwrite previous value'
        );
    }

    public function testDataTypeString(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', '5');
        $result = $this->cache->get('key');
        $this->assertTrue(
            '5' === $result,
            'Wrong data type. If we store a string we must get an string back.'
        );
        $this->assertTrue(
            is_string($result),
            'Wrong data type. If we store a string we must get an string back.'
        );
    }

    public function testDataTypeInteger(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', 5);
        $result = $this->cache->get('key');
        $this->assertTrue(
            5 === $result,
            'Wrong data type. If we store an int we must get an int back.'
        );
        $this->assertTrue(
            is_int($result),
            'Wrong data type. If we store an int we must get an int back.'
        );
    }

    public function testDataTypeFloat(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $float = 1.23456789;
        $this->cache->set('key', $float);
        $result = $this->cache->get('key');
        $this->assertTrue(
            is_float($result),
            'Wrong data type. If we store float we must get an float back.'
        );
        $this->assertEquals($float, $result);
    }

    public function testDataTypeBoolean(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', false);
        $result = $this->cache->get('key');
        $this->assertTrue(
            is_bool($result),
            'Wrong data type. If we store boolean we must get an boolean back.'
        );
        $this->assertFalse($result);
        $this->assertTrue(
            $this->cache->has('key'),
            'has() should return true when true are stored. '
        );
    }

    public function testDataTypeArray(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $array = [
            'a' => 'foo',
            2 => 'bar',
        ];
        $this->cache->set('key', $array);
        $result = $this->cache->get('key');
        $this->assertTrue(
            is_array($result),
            'Wrong data type. If we store array we must get an array back.'
        );
        $this->assertEquals($array, $result);
    }

    public function testDataTypeObject(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $object = new stdClass();
        $object->a = 'foo';
        $this->cache->set('key', $object);
        $result = $this->cache->get('key');
        $this->assertTrue(
            is_object($result),
            'Wrong data type. If we store object we must get an object back.'
        );
        $this->assertEquals($object, $result);
    }

    public function testBinaryData(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $data = '';
        for ($i = 0; $i < 256; $i++) {
            $data .= chr($i);
        }

        $this->cache->set('key', $data);
        $result = $this->cache->get('key');
        $this->assertTrue($data === $result, 'Binary data must survive a round trip.');
    }

    /**
     * @dataProvider validKeys
     */
    public function testSetValidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set($key, 'foobar');
        $this->assertEquals('foobar', $this->cache->get($key));
    }

    /**
     * @dataProvider validKeys
     */
    public function testSetMultipleValidKeys($key): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple([
            $key => 'foobar',
        ]);
        $result = $this->cache->getMultiple([$key]);
        $keys = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertEquals($key, $i);
            $this->assertEquals('foobar', $r);
        }
        $this->assertSame([$key], $keys);
    }

    /**
     * @dataProvider validData
     */
    public function testSetValidData($data): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->set('key', $data);
        $this->assertEquals($data, $this->cache->get('key'));
    }

    /**
     * @dataProvider validData
     */
    public function testSetMultipleValidData($data): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $this->cache->setMultiple([
            'key' => $data,
        ]);
        $result = $this->cache->getMultiple(['key']);
        $keys = [];
        foreach ($result as $i => $r) {
            $keys[] = $i;
            $this->assertEquals($data, $r);
        }
        $this->assertSame(['key'], $keys);
    }

    public function testObjectAsDefaultValue(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $obj = new stdClass();
        $obj->foo = 'value';
        $this->assertEquals($obj, $this->cache->get('key', $obj));
    }

    public function testObjectDoesNotChangeInCache(): void
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        $obj = new stdClass();
        $obj->foo = 'value';
        $this->cache->set('key', $obj);
        $obj->foo = 'changed';

        $cacheObject = $this->cache->get('key');
        $this->assertEquals(
            'value',
            $cacheObject->foo,
            'Object in cache should not have their values changed.'
        );
    }
}
