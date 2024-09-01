<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\ValueObject;

use Webmozart\Assert\Assert;

use function rtrim;

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
    /**
     * @var non-empty-string
     */
    private string $config_dir;

    /**
     * @var non-empty-string
     */
    private string $cache_dir;

    /**
     * @var non-empty-string
     */
    private string $log_dir;

    /**
     * @var non-empty-string
     */
    private string $base_directory;

    public function __construct(string $base_directory, string $config_dir, string $cache_dir, string $log_dir)
    {
        $base_directory = rtrim($base_directory, DIRECTORY_SEPARATOR);
        $config_dir = rtrim($config_dir, DIRECTORY_SEPARATOR);
        $cache_dir = rtrim($cache_dir, DIRECTORY_SEPARATOR);
        $log_dir = rtrim($log_dir, DIRECTORY_SEPARATOR);

        Assert::stringNotEmpty($base_directory, 'The base directory must be a non-empty string.');
        Assert::startsWith(
            $base_directory,
            DIRECTORY_SEPARATOR,
            'The base directory must be an absolute path. Got: %s'
        );
        $this->base_directory = $base_directory;

        Assert::stringNotEmpty($config_dir, 'The config directory must be a non-empty string.');
        Assert::startsWith($config_dir, DIRECTORY_SEPARATOR, 'The config directory must be an absolute path. Got: %s');
        $this->config_dir = $config_dir;

        Assert::stringNotEmpty($cache_dir, 'The cache directory must be a non-empty string.');
        Assert::startsWith($cache_dir, DIRECTORY_SEPARATOR, 'The cache directory must be an absolute path. Got: %s');
        $this->cache_dir = $cache_dir;

        Assert::stringNotEmpty($log_dir, 'The log directory must be a non-empty string.');
        Assert::startsWith($log_dir, DIRECTORY_SEPARATOR, 'The log directory must be an absolute path. Got: %s');
        $this->log_dir = $log_dir;
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

    /**
     * @return non-empty-string
     */
    public function configDir(): string
    {
        return $this->config_dir;
    }

    /**
     * @return non-empty-string
     */
    public function baseDir(): string
    {
        return $this->base_directory;
    }

    /**
     * @return non-empty-string
     */
    public function cacheDir(): string
    {
        return $this->cache_dir;
    }

    /**
     * @return non-empty-string
     */
    public function logDir(): string
    {
        return $this->log_dir;
    }
}
