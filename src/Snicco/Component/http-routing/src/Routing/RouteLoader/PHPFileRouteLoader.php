<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoader;

use Closure;
use FilesystemIterator;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Snicco\Component\HttpRouting\Routing\Exception\InvalidRouteClosureReturned;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\StrArr\Str;
use SplFileInfo;
use Webmozart\Assert\Assert;

use function count;
use function pathinfo;
use function preg_match;

use const PATHINFO_FILENAME;

final class PHPFileRouteLoader implements RouteLoader
{
    /**
     * @var string
     */
    public const VERSION_FLAG = '-v';

    /**
     * @var string
     */
    public const FRONTEND_ROUTE_FILENAME = 'frontend';

    /**
     * @var string
     */
    public const ADMIN_ROUTE_FILENAME = 'admin';

    // Match all files that end with ".php" and don't start with an underscore.
    // https://regexr.com/691di
    /**
     * @var string
     */
    private const SEARCH_PATTERN = '/^[^_].+\.php$/';

    private RouteLoadingOptions $options;

    /**
     * @var string[]
     */
    private array $route_directories = [];

    /**
     * @var string[]
     */
    private array $api_route_directories = [];

    /**
     * @param string[] $route_directories
     * @param string[] $api_route_directories
     */
    public function __construct(array $route_directories, array $api_route_directories, RouteLoadingOptions $options)
    {
        Assert::allString($route_directories, '$route_directories has to be a list of readable directory paths.');
        Assert::allReadable($route_directories, '$route_directories has to be a list of readable directory paths.');
        Assert::allString($api_route_directories, '$api_route_directories has to be a list of valid directory paths.');
        Assert::allReadable(
            $api_route_directories,
            '$api_route_directories has to be a list of valid directory paths.'
        );
        $this->route_directories = $route_directories;
        $this->api_route_directories = $api_route_directories;
        $this->options = $options;
    }

    public function loadWebRoutes(WebRoutingConfigurator $configurator): void
    {
        $this->loadApiRoutesIn($configurator);

        $frontend_routes = null;
        $files = $this->getFiles($this->route_directories);
        foreach ($files as $path => $basename) {
            // Make sure that the frontend.php file is always loaded last
            // because users are expected to register the fallback route there.
            if (self::FRONTEND_ROUTE_FILENAME === $basename) {
                $frontend_routes = $path;

                continue;
            }

            // Admin routes are loaded later in the loadAdminRoutes method.
            if (self::ADMIN_ROUTE_FILENAME === $basename) {
                continue;
            }

            $attributes = $this->options->getRouteAttributes($basename);

            /** @psalm-var Closure(WebRoutingConfigurator) $closure */
            $closure = $this->requireFile($path, $attributes);

            $configurator->group($closure, $attributes);
        }

        if ($frontend_routes) {
            $attributes = $this->options->getRouteAttributes(self::FRONTEND_ROUTE_FILENAME);
            /** @psalm-var Closure(WebRoutingConfigurator) $closure */
            $closure = $this->requireFile($frontend_routes, $attributes);
            $configurator->group($closure, $attributes);
        }
    }

    public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
    {
        $finder = $this->getFiles($this->route_directories);
        foreach ($finder as $path => $basename) {
            if (self::ADMIN_ROUTE_FILENAME !== $basename) {
                continue;
            }

            $attributes = $this->options->getRouteAttributes($basename);

            /** @psalm-var Closure(AdminRoutingConfigurator) $closure */
            $closure = $this->requireFile($path, $attributes, true);

            $configurator->group($closure, $attributes);
        }
    }

    private function loadApiRoutesIn(WebRoutingConfigurator $configurator): void
    {
        foreach ($this->getFiles($this->api_route_directories) as $path => $name) {
            Assert::notEq(
                $name,
                self::FRONTEND_ROUTE_FILENAME,
                sprintf(
                    '[%s] is a reserved filename and can not be loaded as an API file.',
                    self::FRONTEND_ROUTE_FILENAME . '.php'
                )
            );
            Assert::notEq(
                $name,
                self::ADMIN_ROUTE_FILENAME,
                sprintf(
                    '[%s] is a reserved filename and can not be loaded as an API file.',
                    self::ADMIN_ROUTE_FILENAME . '.php'
                )
            );

            [$name, $version] = $this->parseNameAndVersion($name);

            $attributes = $this->options->getApiRouteAttributes($name, $version);

            /** @psalm-var Closure(WebRoutingConfigurator) $closure */
            $closure = $this->requireFile($path, $attributes);

            $configurator->group($closure, $attributes);
        }
    }

    /**
     * @param array{
     *     namespace?:string,
     *     prefix?:string,
     *     name?:string,
     *     middleware?: string[]
     * } $attributes
     *
     * @throws ReflectionException
     */
    private function requireFile(string $file, array $attributes = [], bool $is_admin_file = false): Closure
    {
        $this->validateAttributes($attributes);

        /**
         * @psalm-suppress UnresolvableInclude
         */
        $closure = require $file;

        Assert::isInstanceOf($closure, Closure::class, sprintf('Route file [%s] did not return a closure.', $file));

        $this->validateClosureTypeHint($closure, $file, $is_admin_file);

        return $closure;
    }

    /**
     * @param string[] $route_directories
     *
     * @return array<string,string>
     */
    private function getFiles(array $route_directories): array
    {
        if (empty($route_directories)) {
            return [];
        }

        $files = [];

        foreach ($route_directories as $route_directory) {
            $file_infos = new FilesystemIterator($route_directory);

            /** @var SplFileInfo $file_info */
            foreach ($file_infos as $file_info) {
                if (! $file_info->isFile()) {
                    continue;
                }

                if (! $file_info->isReadable()) {
                    continue;
                }

                if (! preg_match(self::SEARCH_PATTERN, $file_info->getFilename())) {
                    continue;
                }

                $files[$file_info->getRealPath()] = pathinfo($file_info->getRealPath(), PATHINFO_FILENAME);
            }
        }

        return $files;
    }

    /**
     * @param array{
     *     namespace?:string,
     *     prefix?:string,
     *     name?:string,
     *     middleware?: string[]
     * } $attributes
     */
    private function validateAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            switch ($key) {
                case RoutingConfigurator::MIDDLEWARE_KEY:
                    Assert::isArray($value, 'Middleware for route-loading options has to be an array of strings.');
                    Assert::allString(
                        $value,
                        'Middleware for route-loading options has to be an array of strings.'
                    );

                    break;
                case RoutingConfigurator::PREFIX_KEY:
                    Assert::string($value);
                    Assert::startsWith(
                        $value,
                        '/',
                        sprintf(
                            '[%s] has to be a string that starts with a forward slash.',
                            RoutingConfigurator::PREFIX_KEY
                        )
                    );

                    break;
                case RoutingConfigurator::NAMESPACE_KEY:
                    Assert::stringNotEmpty(
                        $value,
                        sprintf('[%s] has to be a non-empty string.', RoutingConfigurator::NAMESPACE_KEY)
                    );

                    break;
                case RoutingConfigurator::NAME_KEY:
                    Assert::stringNotEmpty(
                        $value,
                        sprintf('[%s] has to be a non-empty string.', RoutingConfigurator::NAME_KEY)
                    );

                    break;
                default:
                    throw new InvalidArgumentException(sprintf('The option [%s] is not supported.', $key));
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function validateClosureTypeHint(Closure $closure, string $filepath, bool $is_admin_file = false): void
    {
        $params = (new ReflectionFunction($closure))->getParameters();

        if (1 !== count($params)) {
            throw InvalidRouteClosureReturned::becauseArgumentCountMismatch($filepath, count($params));
        }

        $param = $params[0] ?? null;

        if (! $param instanceof ReflectionParameter || ! $param->getType() instanceof ReflectionNamedType) {
            throw InvalidRouteClosureReturned::becauseTheFirstParameterIsNotTypeHinted($filepath);
        }

        $name = $param->getType()
            ->getName();

        Assert::oneOf(
            $name,
            [RoutingConfigurator::class, AdminRoutingConfigurator::class, WebRoutingConfigurator::class]
        );

        if ($is_admin_file) {
            if (WebRoutingConfigurator::class === $name) {
                throw InvalidRouteClosureReturned::adminRoutesAreUsingWebRouting($filepath);
            }
        } elseif (AdminRoutingConfigurator::class === $name) {
            throw InvalidRouteClosureReturned::webRoutesAreUsingAdminRouting($filepath);
        }
    }

    /**
     * @return array{0:string, 1:?string}
     */
    private function parseNameAndVersion(string $filename): array
    {
        // https://regexr.com/6d3v2
        $pattern = '/^(?:\w+' . self::VERSION_FLAG . ')(\d+)$/';

        $res = preg_match($pattern, $filename, $match);

        if (1 === $res) {
            Assert::keyExists($match, 1);

            /**
             * @psalm-suppress PossiblyUndefinedIntArrayOffset
             */
            return [Str::beforeFirst($filename, self::VERSION_FLAG), $match[1]];
        }

        return [$filename, null];
    }
}
