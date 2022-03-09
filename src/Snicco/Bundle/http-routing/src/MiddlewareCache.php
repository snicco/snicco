<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting;

use Closure;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Webimpress\SafeWriter\Exception\ExceptionInterface;
use Webimpress\SafeWriter\FileWriter;

use function is_array;
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
     * @template return as array{
     *     route_map: array<string, list<array{class: class-string<\Psr\Http\Server\MiddlewareInterface>, args: array<string>}>>,
     *     request_map: array{
     *          api: list<array{class: class-string<\Psr\Http\Server\MiddlewareInterface>, args: array<string>}>,
     *          frontend: list<array{class: class-string<\Psr\Http\Server\MiddlewareInterface>, args: array<string>}>,
     *          admin: list<array{class: class-string<\Psr\Http\Server\MiddlewareInterface>, args: array<string>}>,
     *          global: list<array{class: class-string<\Psr\Http\Server\MiddlewareInterface>, args: array<string>}>
     *      }
     * }
     *
     * @param Closure(): return $loader
     * @throws ExceptionInterface
     */
    public static function get(string $cache_file, Closure $loader): MiddlewareResolver
    {
        set_error_handler(function (): bool {
            return true;
        });

        /**
         * @var mixed $cached_value
         *
         * @psalm-suppress UnresolvableInclude
         */
        $cached_value = include $cache_file;

        restore_error_handler();

        if (is_array($cached_value) && isset($cached_value['route_map']) && isset($cached_value['request_map'])) {
            /** @psalm-suppress MixedArgument */
            return MiddlewareResolver::fromCache($cached_value['route_map'], $cached_value['request_map']);
        }

        $data = $loader();

        FileWriter::writeFile($cache_file, '<?php return ' . var_export($data, true) . ';', 0644);

        return MiddlewareResolver::fromCache($data['route_map'], $data['request_map']);
    }
}
