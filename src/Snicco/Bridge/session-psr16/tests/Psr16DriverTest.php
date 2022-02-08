<?php

declare(strict_types=1);

namespace Snicco\Bridge\SessionPsr16\Tests;

use Cache\Adapter\PHPArray\ArrayCachePool;
use Exception;
use PHPUnit\Framework\TestCase;
use Snicco\Bridge\SessionPsr16\Psr16Driver;
use Snicco\Component\Session\Driver\SessionDriver;
use Snicco\Component\Session\Exception\BadSessionID;
use Snicco\Component\Session\Exception\CantDestroySession;
use Snicco\Component\Session\Exception\CantReadSessionContent;
use Snicco\Component\Session\Exception\CantWriteSessionContent;
use Snicco\Component\Session\Testing\SessionDriverTests;
use Snicco\Component\Session\ValueObject\SerializedSessionData;
use Snicco\Component\TestableClock\Clock;

use function base64_encode;
use function serialize;
use function time;

final class Psr16DriverTest extends TestCase
{

    use SessionDriverTests;

    public function garbage_collection_works_for_old_sessions(): void
    {
        // Automatically
    }

    protected function createDriver(Clock $clock): SessionDriver
    {
        return new Psr16Driver(new ArrayCachePool(), 10);
    }

    /**
     * @test
     */
    public function garbage_collection_works_automatically(): void
    {
        $driver = new Psr16Driver(new ArrayCachePool(), 1);

        $driver->write('session1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));

        sleep(1);

        $this->expectException(BadSessionID::class);

        $driver->read('session1');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_cant_delete_ids(): void
    {
        $cache = new class extends ArrayCachePool {

            public function deleteMultiple($keys)
            {
                return false;
            }

        };

        $driver = new Psr16Driver($cache, 10);

        $driver->write('id1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));

        $this->expectException(CantDestroySession::class);
        $this->expectExceptionMessage('Cant destroy session ids [id1]');

        $driver->destroy(['id1']);
    }

    /**
     * @test
     */
    public function a_custom_exception_is_thrown_if_the_cache_throws_an_invalid_key_exception(): void
    {
        $cache = new class extends ArrayCachePool {

            public function deleteMultiple($keys)
            {
                throw new Exception('bad key');
            }

        };

        $driver = new Psr16Driver($cache, 10);

        $driver->write('id1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));

        $this->expectException(CantDestroySession::class);
        $this->expectExceptionMessage('Cant destroy session ids [id1]');

        $driver->destroy(['id1']);
    }

    /**
     * @test
     */
    public function test_gc_does_nothing(): void
    {
        $driver = new Psr16Driver(new ArrayCachePool(), 10);

        $driver->gc(10);

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function test_exception_if_cache_returns_false_for_save(): void
    {
        $cache = new class extends ArrayCachePool {

            public function set($key, $value, $ttl = null)
            {
                return false;
            }

        };

        $driver = new Psr16Driver($cache, 10);

        $this->expectException(CantWriteSessionContent::class);
        $this->expectExceptionMessage('id1');

        $driver->write('id1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));
    }

    /**
     * @test
     */
    public function test_exception_if_cache_throws_exception_for_save(): void
    {
        $cache = new class extends ArrayCachePool {

            public function set($key, $value, $ttl = null)
            {
                throw new Exception('cant save');
            }

        };

        $driver = new Psr16Driver($cache, 10);

        $this->expectException(CantWriteSessionContent::class);
        $this->expectExceptionMessage('id1');

        $driver->write('id1', SerializedSessionData::fromArray(['foo' => 'bar'], time()));
    }

    /**
     * @test
     */
    public function test_exception_if_reading_throws_an_exception_in_the_cache_driver(): void
    {
        $cache = new class extends ArrayCachePool {

            public function get($key, $default = null)
            {
                throw new Exception('cant read');
            }

        };

        $driver = new Psr16Driver($cache, 10);

        $this->expectException(CantReadSessionContent::class);
        $this->expectExceptionMessage('id1');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_value_is_corrupted_and_cant_be_decoded(): void
    {
        $driver = new Psr16Driver($cache = new ArrayCachePool(), 10);

        $cache->set('id1', '%bad', 10);

        $this->expectException(CantReadSessionContent::class);
        $this->expectExceptionMessage('%bad');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_value_is_not_a_string(): void
    {
        $driver = new Psr16Driver($cache = new ArrayCachePool(), 10);

        $cache->set('id1', 1, 10);

        $this->expectException(CantReadSessionContent::class);
        $this->expectExceptionMessage('is not a string');

        $driver->read('id1');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_value_is_not_valid_string(): void
    {
        $driver = new Psr16Driver($cache = new ArrayCachePool(), 10);

        $cache->set('id1', base64_encode(serialize(['foo' => 'bar'])), 10);

        $this->expectException(CantReadSessionContent::class);
        $this->expectExceptionMessage('corrupted');

        $driver->read('id1');
    }

}