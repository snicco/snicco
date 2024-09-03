<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\Configuration;

use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\Cache\PHPFileCache;
use Snicco\Component\Kernel\Tests\helpers\CleanDirs;
use Webmozart\Assert\Assert;

use function exec;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;

/**
 * @internal
 */
final class PHPFileCacheTest extends TestCase
{
    use CleanDirs;

    /**
     * @var non-empty-string
     */
    private string $cache_dir;

    protected function setUp(): void
    {
        parent::setUp();

        $cache_dir = sys_get_temp_dir() . '/php_file_cache_test';
        if (is_dir($cache_dir)) {
            $this->cleanDirs([$cache_dir]);
            rmdir($cache_dir);
        }
        $this->cache_dir = $cache_dir;
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cache_dir)) {
            $this->cleanDirs([$this->cache_dir]);
            rmdir($this->cache_dir);
        }
        parent::tearDown();
    }

    /**
     * @test
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    public function caching_works_with_the_cache_directory_already_created(): void
    {
        mkdir($this->cache_dir);

        $cache = new PHPFileCache($this->cache_dir);

        $loader = function (): array {
            static $count = 0;
            ++$count;

            return [
                'count' => $count,
            ];
        };

        $this->assertSame([
            'count' => 1,
        ], $cache->getOr('foo', $loader));
        $this->assertSame([
            'count' => 1,
        ], $cache->getOr('foo', $loader));

        exec('rm -rf ' . $this->cache_dir);

        $this->assertSame([
            'count' => 2,
        ], $cache->getOr('foo', $loader));
    }

    /**
     * @test
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedOperand
     */
    public function caching_works_with_the_cache_directory_not_already_created(): void
    {
        Assert::false(is_dir($this->cache_dir));

        $cache = new PHPFileCache($this->cache_dir);

        $loader = function (): array {
            static $count = 0;
            ++$count;

            return [
                'count' => $count,
            ];
        };

        $this->assertSame([
            'count' => 1,
        ], $cache->getOr('foo', $loader));

        $this->assertTrue(is_dir($this->cache_dir));

        $this->assertSame([
            'count' => 1,
        ], $cache->getOr('foo', $loader));
    }
}
