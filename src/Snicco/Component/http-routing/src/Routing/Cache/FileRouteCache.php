<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Cache;

use Closure;
use Webimpress\SafeWriter\FileWriter;

use function dirname;
use function is_array;
use function var_export;

final class FileRouteCache implements RouteCache
{
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

        if (null !== $result) {
            return $result;
        }

        $data = $loader();

        $parent_dir = dirname($this->path);
        if (! is_dir($parent_dir)) {
            // suppress warnings in case multiple requests are trying to create the same directory.
            @mkdir($parent_dir, 0700, true);
        }

        FileWriter::writeFile($this->path, '<?php return ' . var_export($data, true) . ';', 0600);

        return $data;
    }

    /**
     * @return array{url_matcher: array, route_collection: array<string,string>, admin_menu: array<string>}|null
     */
    private function readFile(string $path): ?array
    {
        // error suppression is faster than calling `file_exists()` + `is_file()` + `is_readable()`, especially because there's no need to error here
        set_error_handler($this->empty_error_handler);
        /**  @psalm-suppress UnresolvableInclude */
        $value = include $path;
        restore_error_handler();

        if (! is_array($value)) {
            return null;
        }

        /** @var array{url_matcher: array, route_collection: array<string,string>, admin_menu: array<string>} $value */
        return $value;
    }
}
