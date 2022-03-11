<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Tests\ValueObject;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\Kernel\ValueObject\Directories;

use function dirname;

/**
 * @internal
 */
final class DirectoriesTest extends TestCase
{
    private string $valid_base_dir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->valid_base_dir = dirname(__DIR__) . '/fixtures';
    }

    /**
     * @test
     */
    public function test_from_defaults(): void
    {
        $dirs = Directories::fromDefaults($this->valid_base_dir);

        $this->assertInstanceOf(Directories::class, $dirs);

        $this->assertSame($this->valid_base_dir, $dirs->baseDir());
    }

    /**
     * @test
     */
    public function test_exception_if_base_directory_is_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$base_directory [bogus] is not readable.');

        Directories::fromDefaults('bogus');
    }

    /**
     * @test
     */
    public function test_config_directory(): void
    {
        $dirs = Directories::fromDefaults($this->valid_base_dir);

        $this->assertSame($this->valid_base_dir . '/config', $dirs->configDir());
    }

    /**
     * @test
     */
    public function test_cache_directory(): void
    {
        $dirs = Directories::fromDefaults($this->valid_base_dir);

        $this->assertSame($this->valid_base_dir . '/var/cache', $dirs->cacheDir());
    }

    /**
     * @test
     */
    public function test_log_directory(): void
    {
        $dirs = Directories::fromDefaults($this->valid_base_dir);

        $this->assertSame($this->valid_base_dir . '/var/log', $dirs->logDir());
    }

    /**
     * @test
     */
    public function test_exception_if_config_dir_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('$config_dir [%s] is not readable', __DIR__ . '/config'));

        new Directories(__DIR__, __DIR__ . '/config', __DIR__ . '/cache', __DIR__ . '/log');
    }

    /**
     * @test
     */
    public function test_exception_if_cache_dir_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('$cache_dir [%s] is not readable', __DIR__ . '/cache'));

        new Directories(
            $this->valid_base_dir,
            $this->valid_base_dir . '/config',
            __DIR__ . '/cache',
            __DIR__ . '/log'
        );
    }

    /**
     * @test
     */
    public function test_exception_if_log_dir_not_readable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf('$log_dir [%s] is not readable', __DIR__ . '/log'));

        new Directories(
            $this->valid_base_dir,
            $this->valid_base_dir . '/config',
            $this->valid_base_dir . '/var/cache',
            __DIR__ . '/log'
        );
    }
}
