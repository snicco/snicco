<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing;

use FilesystemIterator;
use PHPUnit\Framework\Assert as PHPUnit;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Component\Kernel\Configuration\ConfigCache;
use Snicco\Component\Kernel\DIContainer;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use Snicco\Component\StrArr\Arr;
use SplFileInfo;

use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function rmdir;
use function unlink;
use function var_export;

trait BootsKernel
{

    protected function container(): DIContainer
    {
        return new PimpleContainerAdapter();
    }

    /**
     * @return array<'testing'|'prod'|'dev'|'staging'|'all', list< class-string<\Snicco\Component\Kernel\Bundle> >>
     */
    abstract protected function bundles(): array;

    protected function setUpDirectories(string $base_dir): Directories
    {
        if (!is_dir($base_dir)) {
            $res = mkdir($base_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create base directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $config_dir = $base_dir . '/config';

        if (!is_dir($config_dir)) {
            $res = mkdir($config_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create config directory');
                // @codeCoverageIgnoreEnd
            }
        }

        if (!is_file($config_dir . '/app.php')) {
            $res = file_put_contents($config_dir . '/app.php', '<?php return ' . var_export([], true) . ';');
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create app.php config file');
                // @codeCoverageIgnoreEnd
            }
        }

        $cache_dir = $base_dir . '/var/cache';

        if (!is_dir($cache_dir)) {
            $res = mkdir($cache_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create cache directory');
                // @codeCoverageIgnoreEnd
            }
        }

        $log_dir = $base_dir . '/var/log';

        if (!is_dir($log_dir)) {
            $res = mkdir($log_dir, 0775, true);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException('Could not create log directory');
                // @codeCoverageIgnoreEnd
            }
        }

        return Directories::fromDefaults($base_dir);
    }

    protected function tearDownDirectories(string $base_dir): void
    {
        $iterator = new RecursiveDirectoryIterator($base_dir, FilesystemIterator::SKIP_DOTS);
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

        $dirs[] = $base_dir;
        foreach ($dirs as $dir) {
            $res = rmdir($dir);
            if (false === $res) {
                // @codeCoverageIgnoreStart
                throw new RuntimeException("Could not remove test directory [$dir].");
                // @codeCoverageIgnoreEnd
            }
        }
    }

    /**
     * @param array<string, array> $config
     */
    protected function bootWithFixedConfig(array $config, Directories $dirs, ?Environment $env = null): Kernel
    {
        if (!Arr::has($config, 'app.bootstrappers')) {
            Arr::set($config, 'app.bootstrappers', []);
        }
        if (!Arr::has($config, 'bundles')) {
            Arr::set($config, 'bundles', $this->bundles());
        }
        $kernel = new Kernel(
            $this->container(),
            $env ?: Environment::testing(),
            $dirs,
            new class($config) implements ConfigCache {

                private array $config;

                public function __construct(array $config)
                {
                    $this->config = $config;
                }

                public function get(string $key, callable $loader): array
                {
                    return $this->config;
                }
            }
        );

        $kernel->boot();

        return $kernel;
    }

    /**
     * @template T
     * @param class-string<T> $class
     */
    protected function assertCanBeResolved(string $class, Kernel $kernel): void
    {
        try {
            /** @var T $resolved */
            $resolved = $kernel->container()->get($class);
        } catch (ContainerExceptionInterface $e) {
            PHPUnit::fail("Class [$class] could not be resolved.\nMessage: " . $e->getMessage());
        }
        PHPUnit::assertInstanceOf($class, $resolved);
    }

    protected function assertNotBound(string $identifier, Kernel $kernel): void
    {
        try {
            $kernel->container()->get($identifier);
            PHPUnit::fail("Identifier [$identifier] was bound in the container.");
        } catch (NotFoundExceptionInterface $e) {
            PHPUnit::assertStringContainsString($identifier, $e->getMessage());
        }
    }

}