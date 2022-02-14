<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;

use Closure;
use RuntimeException;

use function file_put_contents;
use function rename;
use function trigger_error;
use function var_export;

use const E_USER_WARNING;
use const LOCK_EX;

final class FileRouteCache implements RouteCache
{

    private Closure $empty_error_handler;
    private string $path;
    private int $directory_permission;
    private int $file_permission;

    public function __construct(string $cache_path, int $directory_permission = 0755, int $file_permission = 0644)
    {
        $this->path = $cache_path;
        $this->empty_error_handler = function (): void {
        };
        $this->directory_permission = $directory_permission;
        $this->file_permission = $file_permission;
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

        $success = file_put_contents($tmp_file, $content, LOCK_EX);

        if (false === $success) {
            restore_error_handler();
            trigger_error("Could not write cache file to path [$tmp_file].", E_USER_WARNING);
            // @codeCoverageIgnoreStart
            return;
            // @codeCoverageIgnoreEnd
        }

        chmod($tmp_file, $this->file_permission);

        $renamed = rename($tmp_file, $path);
        if (!$renamed) {
            // @codeCoverageIgnoreStart
            trigger_error("Could not rename cache file [$tmp_file] to [$path].", E_USER_WARNING);
            unlink($tmp_file);
            // @codeCoverageIgnoreEnd
        }

        restore_error_handler();
    }

    private function createDirectoryIfNeeded(string $directory): bool
    {
        if (is_dir($directory)) {
            return true;
        }

        set_error_handler($this->empty_error_handler);
        $created = mkdir($directory, $this->directory_permission, true);
        restore_error_handler();

        return $created;
    }

}