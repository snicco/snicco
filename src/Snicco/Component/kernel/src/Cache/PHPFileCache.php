<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Cache;

use Closure;
use Webimpress\SafeWriter\FileWriter;

use function is_array;
use function is_dir;
use function restore_error_handler;
use function rtrim;
use function set_error_handler;
use function str_replace;
use function var_export;

use const DIRECTORY_SEPARATOR;

/**
 * @interal
 *
 * @psalm-internal Snicco
 */
final class PHPFileCache implements BootstrapCache
{
    private Closure $empty_error_handler;

    /**
     * @var non-empty-string
     */
    private string $cache_dir;

    /**
     * @param non-empty-string $cache_dir
     */
    public function __construct(string $cache_dir)
    {
        $this->empty_error_handler = fn (): bool => true;
        $this->cache_dir = $cache_dir;
    }

    public function getOr(string $cache_key, callable $loader): array
    {
        $cache_key = str_replace(['/', '-'], '_', $cache_key);

        $cache_file = rtrim($this->cache_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $cache_key . '.php';

        $config = $this->readFile($cache_file);

        if (null !== $config) {
            return $config;
        }

        $config = $loader();

        if (! is_dir($this->cache_dir)) {
            // Suppress warnings in case multiple requests are trying to create the same directory.
            // If the directory can't be created, FileWriter::writeFile will throw an exception.
            @mkdir($this->cache_dir, 0700, true);
        }

        $this->writeFileAtomic($cache_file, $config);

        return $config;
    }

    /**
     * @psalm-suppress UnresolvableInclude $value
     */
    private function readFile(string $file): ?array
    {
        // error suppression is faster than calling `is_file()` + `is_readable()`,
        // especially because there's no need to error here.
        set_error_handler($this->empty_error_handler);

        try {
            $value = include $file;
        } finally {
            restore_error_handler();
        }

        if (! is_array($value)) {
            return null;
        }

        return $value;
    }

    private function writeFileAtomic(string $cache_file, array $config): void
    {
        FileWriter::writeFile($cache_file, '<?php return ' . var_export($config, true) . ';', 0600);
    }
}
