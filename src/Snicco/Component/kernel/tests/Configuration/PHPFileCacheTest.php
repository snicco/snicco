<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\Kernel\Configuration\PHPFileCache;

use function is_file;
use function sys_get_temp_dir;
use function unlink;

/**
 * @internal
 */
final class PHPFileCacheTest extends TestCase
{
    private string $cache_file;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache_file = __DIR__ . '/test_cache.php';
        if (is_file($this->cache_file)) {
            unlink($this->cache_file);
        }
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        if (is_file($this->cache_file)) {
            unlink($this->cache_file);
        }
    }

    /**
     * @test
     */
    public function the_config_is_stored_and_returned(): void
    {
        $cache = new PHPFileCache();

        $this->assertFalse(is_file($this->cache_file));

        $res = $cache->get($this->cache_file, fn (): array => [
            'foo' => 'bar',
        ]);

        $this->assertSame([
            'foo' => 'bar',
        ], $res);

        $this->assertTrue(is_file($this->cache_file));

        $this->assertSame([
            'foo' => 'bar',
        ], require $this->cache_file);
    }

    /**
     * @test
     */
    public function the_config_is_not_reloaded_if_loaded_already(): void
    {
        $cache = new PHPFileCache();

        $this->assertFalse(is_file($this->cache_file));

        $cache->get($this->cache_file, fn (): array => [
            'foo' => 'bar',
        ]);

        $new = new PHPFileCache();
        $res = $new->get($this->cache_file, function (): void {
            throw new RuntimeException('This should never be called.');
        });

        $this->assertSame([
            'foo' => 'bar',
        ], $res);
    }

    /**
     * @test
     */
    public function will_create_the_cache_directory_if_not_exists(): void
    {
        $cache = new PHPFileCache();

        $file = sys_get_temp_dir() . '/php_file_cache_test_non_existing_dir/test_cache.php';

        $cache->get($file, fn (): array => [
            'foo' => 'bar',
        ]);

        $this->assertTrue(is_file($file));
    }
}
