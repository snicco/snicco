<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Cache;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\HttpRouting\Routing\Cache\FileRouteCache;

use function is_file;
use function unlink;

/**
 * @internal
 */
final class FileRouteCacheTest extends TestCase
{
    private string $test_cache_file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->test_cache_file = __DIR__ . '/cache.php';
        $this->resetDir();
    }

    protected function tearDown(): void
    {
        $this->resetDir();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function file_contents_are_written(): void
    {
        $cache = new FileRouteCache($this->test_cache_file);

        $this->assertFalse(is_file($this->test_cache_file));

        $res = $cache->get(function () {
            return [
                'route_collection' => [
                    'foo' => 'bar',
                ],
                'url_matcher' => [],
                'admin_menu' => [],
            ];
        });

        $this->assertSame([
            'route_collection' => [
                'foo' => 'bar',
            ],
            'url_matcher' => [],
            'admin_menu' => [],
        ], $res);

        $this->assertTrue(is_file($this->test_cache_file));
    }

    /**
     * @test
     */
    public function the_loader_callable_is_not_run_if_a_cache_file_exists(): void
    {
        $cache = new FileRouteCache($this->test_cache_file);

        $this->assertFalse(is_file($this->test_cache_file));

        $cache->get(function () {
            return [
                'route_collection' => [
                    'foo' => 'bar',
                ],
                'url_matcher' => [],
                'admin_menu' => [],
            ];
        });

        $this->assertTrue(is_file($this->test_cache_file));

        $this->assertSame(
            [
                'route_collection' => [
                    'foo' => 'bar',
                ],
                'url_matcher' => [],
                'admin_menu' => [],
            ],
            $cache->get(function () {
                throw new RuntimeException('Data not loaded from cache.');
            })
        );
    }

    protected function resetDir(): void
    {
        if (is_file($this->test_cache_file)) {
            $res = unlink($this->test_cache_file);
            if (false === $res) {
                throw new RuntimeException('Cant remove cache file.');
            }
        }
    }
}
