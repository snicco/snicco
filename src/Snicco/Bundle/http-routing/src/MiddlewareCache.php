<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use Closure;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Webimpress\SafeWriter\Exception\ExceptionInterface;
use Webimpress\SafeWriter\FileWriter;

use function dirname;
use function is_array;
use function is_dir;
use function mkdir;
use function restore_error_handler;
use function set_error_handler;
use function var_export;

/**
 * @psalm-internal Snicco\Bundle\HttpRouting
 *
 * @interal
 */
final class MiddlewareCache
{
    /**
     * @template return as array{route_map: array<string, list<array{class: class-string<MiddlewareInterface>, args: array<string>}>>, request_map: array{api: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>, frontend: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>, admin: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>, global: list<array{class: class-string<MiddlewareInterface>, args: array<string>}>}}
     *
     * @param Closure(): return $loader
     *
     * @throws ExceptionInterface
     */
    public static function get(string $cache_file, Closure $loader): MiddlewareResolver
    {
        set_error_handler(fn (): bool => true);

        /**
         * @var mixed $cached_value
         *
         * @psalm-suppress UnresolvableInclude
         */
        $cached_value = include $cache_file;

        restore_error_handler();

        if (is_array($cached_value) && isset($cached_value['route_map'], $cached_value['request_map'])) {
            /** @psalm-suppress MixedArgument */
            return MiddlewareResolver::fromCache($cached_value['route_map'], $cached_value['request_map']);
        }

        $data = $loader();

        $parent_dir = dirname($cache_file);
        if (! is_dir($parent_dir)) {
            // suppress warnings in case multiple requests are trying to create the same directory.
            @mkdir($parent_dir, 0700, true);
        }

        FileWriter::writeFile($cache_file, '<?php return ' . var_export($data, true) . ';', 0600);

        return MiddlewareResolver::fromCache($data['route_map'], $data['request_map']);
    }
}
