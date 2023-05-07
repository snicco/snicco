<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\ValueObject;

use Webmozart\Assert\Assert;

use function sprintf;

use const DIRECTORY_SEPARATOR;

/**
 * This class represents a simple value object that holds references to all
 * relevant directories the app needs. All directories are validated to be
 * readable and don't end with a trailing slash.
 *
 * @psalm-immutable
 */
final class Directories
{
    private string $config_dir;

    private string $cache_dir;

    private string $log_dir;

    private string $base_directory;

    public function __construct(string $base_directory, string $config_dir, string $cache_dir, string $log_dir)
    {
        Assert::readable($base_directory, sprintf('$base_directory [%s] is not readable.', $base_directory));

        Assert::readable($config_dir, sprintf('$config_dir [%s] is not readable.', $config_dir));

        Assert::readable($cache_dir, sprintf('$cache_dir [%s] is not readable.', $cache_dir));
        Assert::writable($cache_dir, sprintf('$cache_dir [%s] is not writable.', $cache_dir));

        Assert::readable($log_dir, sprintf('$log_dir [%s] is not readable.', $log_dir));
        Assert::writable($log_dir, sprintf('$log_dir [%s] is not writable.', $log_dir));

        $this->config_dir = rtrim($config_dir, DIRECTORY_SEPARATOR);
        $this->cache_dir = rtrim($cache_dir, DIRECTORY_SEPARATOR);
        $this->log_dir = rtrim($log_dir, DIRECTORY_SEPARATOR);
        $this->base_directory = rtrim($base_directory, DIRECTORY_SEPARATOR);
    }

    public static function fromDefaults(string $base_directory): Directories
    {
        $config_dir = rtrim($base_directory, DIRECTORY_SEPARATOR) .
            DIRECTORY_SEPARATOR .
            'config';

        $cache_dir = rtrim($base_directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'var'
            . DIRECTORY_SEPARATOR
            . 'cache';

        $log_dir = rtrim($base_directory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . 'var'
            . DIRECTORY_SEPARATOR
            . 'log';

        return new self($base_directory, $config_dir, $cache_dir, $log_dir);
    }

    public function configDir(): string
    {
        return $this->config_dir;
    }

    public function baseDir(): string
    {
        return $this->base_directory;
    }

    public function cacheDir(): string
    {
        return $this->cache_dir;
    }

    public function logDir(): string
    {
        return $this->log_dir;
    }
}
