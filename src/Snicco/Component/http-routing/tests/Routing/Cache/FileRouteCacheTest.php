<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Routing\Cache;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\HttpRouting\Routing\Cache\FileRouteCache;

use function chmod;
use function dirname;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function unlink;

final class FileRouteCacheTest extends TestCase
{

    private string $fixtures_dir;
    private string $test_cache_file;
    private string $cache_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fixtures_dir = dirname(__DIR__, 2) . '/fixtures/cache';
        $this->cache_dir = $this->fixtures_dir . '/route-cache';
        $this->test_cache_file = $this->cache_dir . '/cache.php';

        $this->resetDir();
    }

    protected function tearDown(): void
    {
        $res = chmod($this->fixtures_dir, 0755);
        if (false === $res) {
            throw new RuntimeException('Cant revert file permissions.');
        }
        $this->resetDir();

        parent::tearDown();
    }

    /**
     * @test
     */
    public function test_exception_if_directory_cant_be_created(): void
    {
        $res = chmod($this->fixtures_dir, 400);
        if (false === $res) {
            throw new RuntimeException('Cant change file permissions.');
        }

        $cache = new FileRouteCache($this->test_cache_file);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('not writable');

        $cache->get(function () {
            return [
                'route_collection' => ['foo' => 'bar'],
                'url_matcher' => []
            ];
        });
    }

    /**
     * @test
     */
    public function test_warning_if_fail_contents_cant_be_written(): void
    {
        $this->expectWarning();
        $this->expectWarningMessage('Could not write cache file');

        mkdir($this->cache_dir);
        chmod($this->cache_dir, 444);

        $cache = new FileRouteCache($this->test_cache_file);

        $res = $cache->get(function () {
            return [
                'route_collection' => ['foo' => 'bar'],
                'url_matcher' => []
            ];
        });

        $this->assertFalse(is_file($this->test_cache_file));

        $this->assertSame([
            'route_collection' => ['foo' => 'bar'],
            'url_matcher' => []
        ], $res);
    }

    protected function resetDir(): void
    {
        if (is_file($this->test_cache_file)) {
            $res = unlink($this->test_cache_file);
            if (false === $res) {
                throw new RuntimeException('Cant remove cache file.');
            }
        }
        if (is_dir($this->cache_dir)) {
            $res = rmdir($this->cache_dir);
            if (false === $res) {
                throw new RuntimeException('Cant remove cache dir.');
            }
        }
    }

}