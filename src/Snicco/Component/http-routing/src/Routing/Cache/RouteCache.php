<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Routing\Cache;

use Closure;
use RuntimeException;

use function var_export;

final class RouteCache
{
    private const DIRECTORY_PERMISSIONS = 0775;
    private const FILE_PERMISSIONS = 0664;

    private Closure $empty_error_handler;

    /**
     * @var Closure():?array{fast_route: array, route_collection: string}
     */
    private Closure $loader;

    private string $path;

    /**
     * @param Closure():?array{fast_route: array, route_collection: string} $loader
     * @param string $cache_path
     */
    public function __construct(Closure $loader, string $cache_path)
    {
        $this->loader = $loader;
        $this->path = $cache_path;
        $this->empty_error_handler = function (): void {
        };
    }

    /**
     * @return null|array{fast_route: array, route_collection: string}
     */
    public function get(): ?array
    {
        $result = $this->readFile($this->path);

        if ($result !== null) {
            return $result;
        }

        $loader = $this->loader;
        $data = $loader();
        $this->writeToFile($this->path, '<?php return ' . var_export($data, true) . ';');

        return $data;
    }

    /**
     * @psalm-suppress UnresolvableInclude
     * @psalm-suppress MixedReturnTypeCoercion
     *
     * @return null|array{fast_route: array, route_collection: string}
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

        return $value;
    }

    private function writeToFile(string $path, string $content): void
    {
        $directory = dirname($path);

        if (!$this->createDirectoryIfNeeded($directory) || !is_writable($directory)) {
            throw new RuntimeException('The cache directory is not writable "' . $directory . '"');
        }

        set_error_handler($this->empty_error_handler);

        $tmpFile = $path . '.tmp';

        if (file_put_contents($tmpFile, $content, LOCK_EX) === false) {
            restore_error_handler();
            return;
        }

        chmod($tmpFile, self::FILE_PERMISSIONS);

        if (!rename($tmpFile, $path)) {
            unlink($tmpFile);
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