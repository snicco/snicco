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
    public function exception_if_base_dir_not_absolute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The base directory must be an absolute path. Got: "relative/path"');

        Directories::fromDefaults('relative/path');
    }

    /**
     * @test
     */
    public function exception_if_config_dir_relative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The config directory must be an absolute path. Got: "relative/path"');

        new Directories('/base', 'relative/path', '/cache', '/log');
    }

    /**
     * @test
     */
    public function exception_if_cache_dir_relative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The cache directory must be an absolute path. Got: "relative/path"');

        new Directories('/base', '/config', 'relative/path', '/log');
    }

    /**
     * @test
     */
    public function exception_if_log_dir_relative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The log directory must be an absolute path. Got: "relative/path"');

        new Directories('/base', '/config', '/cache', 'relative/path');
    }
}
