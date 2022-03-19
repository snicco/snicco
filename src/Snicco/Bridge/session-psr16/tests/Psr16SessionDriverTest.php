<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Bridge\SessionPsr16\Psr16SessionDriver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\CouldNotDestroySession;
use Snicco\Component\Session\Exception\CouldNotReadSessionContent;
use Snicco\Component\Session\Exception\CouldNotWriteSessionContent;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\ValueObject\SerializedSession;
use Snicco\Component\TestableClock\Clock;
use Snicco\Component\TestableClock\TestClock;

use function sleep;
use function time;

/**
 * @internal
 */
final class Psr16SessionDriverTest extends TestCase
{
    use SessionDriverTests;

    /**
     * @test
     */
    public function test_exception_if_cache_cant_delete_ids(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function delete($key): bool
            {
                return false;
            }
        };

        $driver = new Psr16SessionDriver($cache, 10);

        $driver->write('id1', SerializedSession::fromString('foo', 'val', time()),);

        $this->expectException(CouldNotDestroySession::class);
        $this->expectExceptionMessage('Cant destroy session with selector [id1]');

        $driver->destroy('id1');
    }

    /**
     * @test
     */
    public function a_custom_exception_is_thrown_if_the_cache_throws_an_invalid_key_exception(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function delete($key): void
            {
                throw new Exception('bad key');
            }
        };

        $driver = new Psr16SessionDriver($cache, 10);

        $driver->write('id1', SerializedSession::fromString('foo', 'val', time()));

        $this->expectException(CouldNotDestroySession::class);
        $this->expectExceptionMessage('Cant destroy session with selector [id1]');

        $driver->destroy('id1');
    }

    /**
     * @test
     */
    public function test_gc_does_nothing(): void
    {
        $driver = new Psr16SessionDriver(new ArrayCachePool(), 10);

        // @noRector
        $driver->gc(10);

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function test_exception_if_cache_returns_false_for_save(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function set($key, $value, $ttl = null): bool
            {
                return false;
            }
        };

        $driver = new Psr16SessionDriver($cache, 10);

        $this->expectException(CouldNotWriteSessionContent::class);
        $this->expectExceptionMessage('id1');

        $driver->write('id1', SerializedSession::fromString('foo', 'val', time()),);
    }

    /**
     * @test
     */
    public function test_exception_if_cache_throws_exception_for_set(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function set($key, $value, $ttl = null): void
            {
                throw new Exception('cant save');
            }
        };

        $driver = new Psr16SessionDriver($cache, 10);

        $this->expectException(CouldNotWriteSessionContent::class);
        $this->expectExceptionMessage('id1');

        $driver->write('id1', SerializedSession::fromString('foo', 'val', time()),);
    }

    /**
     * @test
     */
    public function test_exception_if_reading_throws_an_exception_in_the_cache_driver(): void
    {
        $cache = new class() extends ArrayCachePool {
            public function get($key, $default = null): void
            {
                throw new Exception('cant read');
            }
        };

        $driver = new Psr16SessionDriver($cache, 10);

        $this->expectException(CouldNotReadSessionContent::class);
        $this->expectExceptionMessage('id1');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_value_is_not_an_array(): void
    {
        $driver = new Psr16SessionDriver($cache = new ArrayCachePool(), 10);

        $cache->set('id1', 1, 10);

        $this->expectException(CouldNotReadSessionContent::class);
        $this->expectExceptionMessage('is not an array');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_last_activity_is_not_int(): void
    {
        $driver = new Psr16SessionDriver($cache = new ArrayCachePool(), 10);

        $cache->set(
            'id1',
            [
                'last_activity' => true,
                'data' => 'string',
                'user_id' => null,
                'hashed_validator' => 'val',
            ],
            10
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache corrupted. [last_activity] is not an integer for selector [id1].');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_data_is_not_string(): void
    {
        $driver = new Psr16SessionDriver($cache = new ArrayCachePool(), 10);

        $cache->set(
            'id1',
            [
                'last_activity' => 10,
                'data' => true,
                'user_id' => null,
                'hashed_validator' => 'val',
            ],
            10
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache corrupted. [data] is not a string for selector [id1].');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_hashed_validator_is_not_string(): void
    {
        $driver = new Psr16SessionDriver($cache = new ArrayCachePool(), 10);

        $cache->set(
            'id1',
            [
                'last_activity' => 10,
                'data' => 'string',
                'user_id' => null,
                'hashed_validator' => true,
            ],
            10
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache corrupted. [hashed_validator] is not a string for selector [id1].');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_user_id_is_wrong(): void
    {
        $driver = new Psr16SessionDriver($cache = new ArrayCachePool(), 10);

        $cache->set(
            'id1',
            [
                'last_activity' => 10,
                'data' => 'string',
                'user_id' => true,
                'hashed_validator' => 'val',
            ],
            10
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cache corrupted. [user_id] is not a null,string or integer for selector [id1].');

        $driver->read('id1');
    }

    /**
     * @param 0|positive-int $seconds
     */
    protected function travelIntoFuture(TestClock $clock, int $seconds): void
    {
        sleep($seconds);
    }

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new Psr16SessionDriver(new ArrayCachePool(), $this->idleTimeout());
    }
}
