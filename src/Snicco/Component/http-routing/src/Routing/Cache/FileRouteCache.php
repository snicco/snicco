<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Cache;

use Closure;
use Webimpress\SafeWriter\Exception\ExceptionInterface;
use Webimpress\SafeWriter\FileWriter;

use function is_array;
use function var_export;

final class FileRouteCache implements RouteCache
{
    private Closure $empty_error_handler;

    private string $path;

    private int $file_permission;

    public function __construct(string $cache_path, int $file_permission = 0644)
    {
        $this->path = $cache_path;
        $this->empty_error_handler = function (): void {
        };
        $this->file_permission = $file_permission;
    }

    public function get(callable $loader): array
    {
        $result = $this->readFile($this->path);

        if (null !== $result) {
            return $result;
        }

        $data = $loader();
        $this->writeToFile($this->path, '<?php return ' . var_export($data, true) . ';');

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

    /**
     * @throws ExceptionInterface
     */
    private function writeToFile(string $path, string $content): void
    {
        FileWriter::writeFile($path, $content, $this->file_permission);
    }
}
