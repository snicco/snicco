<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Configuration;

use Closure;
use Webimpress\SafeWriter\FileWriter;

use function dirname;
use function is_array;
use function is_dir;
use function restore_error_handler;
use function set_error_handler;
use function var_export;

final class PHPFileCache implements ConfigCache
{
    private Closure $empty_error_handler;

    public function __construct()
    {
        $this->empty_error_handler = function (): void {
        };
    }

    public function get(string $key, callable $loader): array
    {
        $config = $this->read($key);

        if (null !== $config) {
            return $config;
        }

        $config = $loader();

        $cache_file_parent_dir = dirname($key);
        if (! is_dir($cache_file_parent_dir)) {
            // suppress warnings in case multiple requests are trying to create the same directory.
            @mkdir($cache_file_parent_dir, 0755, true);
        }

        FileWriter::writeFile($key, '<?php return ' . var_export($config, true) . ';', 0644);

        return $config;
    }

    private function read(string $file): ?array
    {
        // error suppression is faster than calling `file_exists()` + `is_file()` + `is_readable()`, especially because there's no need to error here
        set_error_handler($this->empty_error_handler);
        /** @psalm-suppress UnresolvableInclude $value */
        $value = include $file;
        restore_error_handler();

        if (! is_array($value)) {
            return null;
        }

        return $value;
    }
}
