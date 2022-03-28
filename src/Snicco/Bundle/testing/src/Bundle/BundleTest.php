<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Bundle;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\TestErrorHandler;
use SplFileInfo;

use function in_array;
use function is_dir;
use function mkdir;
use function rmdir;
use function unlink;

/**
 * @internal
 */
final class BundleTest
{
    private string $fixtures_dir;

    /**
     * @var string[]
     */
    private array $fixture_config_files = [];

    /**
     * @psalm-suppress PropertyNotSetInConstructor
     */
    private Directories $directories;

    public function __construct(string $fixtures_dir)
    {
        $this->fixtures_dir = $fixtures_dir;
    }

    public function newContainer(): PimpleContainerAdapter
    {
        return new PimpleContainerAdapter();
    }

    public function withoutHttpErrorHandling(Kernel $kernel): void
    {
        $kernel->afterRegister(function (Kernel $kernel): void {
            $kernel->container()
                ->instance(HttpErrorHandler::class, new TestErrorHandler());
        });
    }

    public function setUpDirectories(): Directories
    {
        $fixtures_dir = $this->fixtures_dir;

        if (! is_dir($fixtures_dir)) {
            $res = mkdir($fixtures_dir, 0775, true);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create base directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $config_dir = $fixtures_dir . '/config';

        if (! is_dir($config_dir)) {
            $res = mkdir($config_dir, 0775, true);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create config directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $cache_dir = $fixtures_dir . '/var/cache';

        if (! is_dir($cache_dir)) {
            $res = mkdir($cache_dir, 0775, true);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create cache directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $log_dir = $fixtures_dir . '/var/log';

        if (! is_dir($log_dir)) {
            $res = mkdir($log_dir, 0775, true);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create log directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $directories = Directories::fromDefaults($fixtures_dir);
        $iterator = new RecursiveDirectoryIterator($directories->configDir());

        /**
         * @var SplFileInfo $file_info
         * @var string      $path
         */
        foreach ($iterator as $path => $file_info) {
            if ($file_info->isFile() && 'php' === $file_info->getExtension()) {
                $this->fixture_config_files[] = $path;
            }
        }

        $this->directories = $directories;

        $this->tearDownDirectories();

        return $directories;
    }

    /**
     * @param string[] $expect
     */
    public function removePHPFilesRecursive(string $base_dir, array $expect = []): void
    {
        $iterator = new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS);
        $objects = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
        $files = [];

        /**
         * @var string      $name
         * @var SplFileInfo $file_info
         */
        foreach ($objects as $name => $file_info) {
            if ($file_info->isDir()) {
                continue;
            }

            if ($file_info->isFile() && 'php' === $file_info->getExtension() && ! in_array($name, $expect, true)) {
                $files[] = $name;
            }
        }

        foreach ($files as $file) {
            $res = unlink($file);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(sprintf('Could not remove test fixture file [%s].', $file));
                // @codeCoverageIgnoreEnd
            }
        }
    }

    /**
     * This method will remove the provided directory recursively. You have been
     * warned.
     */
    public function removeDirectoryRecursive(string $directory): void
    {
        $iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
        $objects = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
        $files = [];
        $dirs = [];
        /**
         * @var string      $name
         * @var SplFileInfo $file_info
         */
        foreach ($objects as $name => $file_info) {
            if ($file_info->isDir()) {
                $dirs[] = $name;
            } elseif ($file_info->isFile()) {
                $files[] = $name;
            }
        }

        foreach ($files as $file) {
            $res = unlink($file);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(sprintf('Could not remove test fixture file [%s].', $file));
                // @codeCoverageIgnoreEnd
            }
        }

        $dirs[] = $directory;

        foreach ($dirs as $dir) {
            $res = rmdir($dir);
            if (! $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException(sprintf('Could not remove test directory [%s].', $dir));
                // @codeCoverageIgnoreEnd
            }
        }
    }

    public function tearDownDirectories(): void
    {
        $this->removePHPFilesRecursive($this->directories->cacheDir());
        $this->removePHPFilesRecursive($this->directories->logDir());
        $this->removePHPFilesRecursive($this->directories->configDir(), $this->fixture_config_files);
    }
}
