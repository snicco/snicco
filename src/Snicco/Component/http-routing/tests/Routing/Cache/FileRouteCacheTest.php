<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Cache;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\HttpRouting\Routing\Cache\FileRouteCache;

use function exec;
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

        $res = $cache->get(fn (): array => [
            'route_collection' => [
                'foo' => 'bar',
            ],
            'url_matcher' => [],
            'admin_menu' => [],
        ]);

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
    public function will_try_to_create_non_existing_cache_directory(): void
    {
        $cache = new FileRouteCache('/tmp/snicco-file-route-cache-test/cache.php');

        $this->assertFalse(is_file('/tmp/snicco-file-route-cache-test/cache.php'));

        $res = $cache->get(fn (): array => [
            'route_collection' => [
                'foo' => 'bar',
            ],
            'url_matcher' => [],
            'admin_menu' => [],
        ]);

        $this->assertSame([
            'route_collection' => [
                'foo' => 'bar',
            ],
            'url_matcher' => [],
            'admin_menu' => [],
        ], $res);

        $this->assertTrue(is_file('/tmp/snicco-file-route-cache-test/cache.php'));
    }

    /**
     * @test
     */
    public function the_loader_callable_is_not_run_if_a_cache_file_exists(): void
    {
        $cache = new FileRouteCache($this->test_cache_file);

        $this->assertFalse(is_file($this->test_cache_file));

        $cache->get(fn (): array => [
            'route_collection' => [
                'foo' => 'bar',
            ],
            'url_matcher' => [],
            'admin_menu' => [],
        ]);

        $this->assertTrue(is_file($this->test_cache_file));

        $this->assertSame(
            [
                'route_collection' => [
                    'foo' => 'bar',
                ],
                'url_matcher' => [],
                'admin_menu' => [],
            ],
            $cache->get(function (): void {
                throw new RuntimeException('Data not loaded from cache.');
            })
        );
    }

    private function resetDir(): void
    {
        if (is_file($this->test_cache_file)) {
            $res = unlink($this->test_cache_file);
            if (! $res) {
                throw new RuntimeException('Cant remove cache file.');
            }
        }

        exec('rm -rf /tmp/snicco-file-route-cache-test');
    }
}
