<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;

use Closure;
use RuntimeException;

use function var_export;

final class FileRouteCache implements RouteCache
{
    private const DIRECTORY_PERMISSIONS = 0775;
    private const FILE_PERMISSIONS = 0664;

    private Closure $empty_error_handler;
    private string $path;

    public function __construct(string $cache_path)
    {
        $this->path = $cache_path;
        $this->empty_error_handler = function (): void {
        };
    }

    public function get(callable $loader): array
    {
        $result = $this->readFile($this->path);

        if ($result !== null) {
            return $result;
        }
        $data = $loader();
        $this->writeToFile($this->path, '<?php return ' . var_export($data, true) . ';');

        return $data;
    }

    /**
     * @psalm-suppress UnresolvableInclude
     *
     * @return null|array{url_matcher: array, route_collection: array<string,string>}
     */
    private function readFile(string $path): ?array
    {
        // error suppression is faster than calling `file_exists()` + `is_file()` + `is_readable()`, especially because there's no need to error here
        set_error_handler($this->empty_error_handler);
        $value = include $path;
        restore_error_handler();

        if (!is_array($value)) {
            return null;
        }

        /** @var array{url_matcher: array, route_collection: array<string,string>} $value */
        return $value;
    }

    private function writeToFile(string $path, string $content): void
    {
        $directory = dirname($path);

        if (!$this->createDirectoryIfNeeded($directory) || !is_writable($directory)) {
            throw new RuntimeException("The cache directory is not writable.\n[$directory].");
        }

        set_error_handler($this->empty_error_handler);

        $tmp_file = $path . '.tmp';

        if (false === file_put_contents($tmp_file, $content, LOCK_EX)) {
            restore_error_handler();
            return;
        }

        chmod($tmp_file, self::FILE_PERMISSIONS);

        if (!rename($tmp_file, $path)) {
            unlink($tmp_file);
        }

        restore_error_handler();
    }

    private function createDirectoryIfNeeded(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        set_error_handler($this->empty_error_handler);
        $created = mkdir($directory, self::DIRECTORY_PERMISSIONS, true);
        restore_error_handler();

        return $created || is_dir($directory);
    }

}