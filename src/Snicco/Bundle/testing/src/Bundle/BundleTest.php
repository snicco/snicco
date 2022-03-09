<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Bundle;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandler;
use Snicco\Component\Psr7ErrorHandler\TestErrorHandler;
use SplFileInfo;

use function file_put_contents;
use function in_array;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function unlink;
use function var_export;

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

    public function newContainer(): DIContainer
    {
        return new PimpleContainerAdapter();
    }

    public function withoutHttpErrorHandling(Kernel $kernel): void
    {
        $kernel->afterRegister(function (Kernel $kernel) {
            $kernel->container()->instance(
                HttpErrorHandler::class,
                new TestErrorHandler()
            );
        });
    }

    public function setUpDirectories(): Directories
    {
        $fixtures_dir = $this->fixtures_dir;

        if (! is_dir($fixtures_dir)) {
            $res = mkdir($fixtures_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create base directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $config_dir = $fixtures_dir . '/config';

        if (! is_dir($config_dir)) {
            $res = mkdir($config_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create config directory');
                // @codeCoverageIgnoreEnd
            }
        }

        if (! is_file($config_dir . '/app.php')) {
            $res = file_put_contents($config_dir . '/app.php', '<?php return ' . var_export([], true) . ';');
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create app.php config file');
                // @codeCoverageIgnoreEnd
            }
        }

        $cache_dir = $fixtures_dir . '/var/cache';

        if (! is_dir($cache_dir)) {
            $res = mkdir($cache_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create cache directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $log_dir = $fixtures_dir . '/var/log';

        if (! is_dir($log_dir)) {
            $res = mkdir($log_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create log directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $directories = Directories::fromDefaults($fixtures_dir);
        $iterator = new RecursiveDirectoryIterator($directories->configDir());

        /**
         * @var SplFileInfo $file_info
         * @var string $path
         */
        foreach ($iterator as $path => $file_info) {
            if ($file_info->isFile() && $file_info->getExtension() === 'php') {
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
         * @var string $name
         * @var SplFileInfo $file_info
         */
        foreach ($objects as $name => $file_info) {
            if ($file_info->isDir()) {
                continue;
            } elseif ($file_info->isFile() && $file_info->getExtension() === 'php' && ! in_array($name, $expect, true)) {
                $files[] = $name;
            }
        }

        foreach ($files as $file) {
            $res = unlink($file);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException("Could not remove test fixture file [$file].");
                // @codeCoverageIgnoreEnd
            }
        }
    }

    /**
     * This method will remove the provided directory recursively. You have been warnded.
     */
    public function removeDirectoryRecursive(string $directory): void
    {
        $iterator = new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS);
        $objects = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);
        $files = [];
        $dirs = [];
        /**
         * @var string $name
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
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException("Could not remove test fixture file [$file].");
                // @codeCoverageIgnoreEnd
            }
        }

        $dirs[] = $directory;

        foreach ($dirs as $dir) {
            $res = rmdir($dir);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException("Could not remove test directory [$dir].");
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
