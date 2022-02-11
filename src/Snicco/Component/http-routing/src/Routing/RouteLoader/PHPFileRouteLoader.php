<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoader;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Snicco\Component\HttpRouting\Reflection;
use Snicco\Component\HttpRouting\Routing\Exception\InvalidRouteClosureReturned;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\StrArr\Str;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Webmozart\Assert\Assert;

use function iterator_to_array;


final class PHPFileRouteLoader implements RouteLoader
{

    public const VERSION_FLAG = '-v';
    public const FRONTEND_ROUTE_FILENAME = 'frontend';
    public const ADMIN_ROUTE_FILENAME = 'admin';

    // Match all files that end with ".php" and don't start with an underscore.
    // https://regexr.com/691di
    private const SEARCH_PATTERN = '/^[^_].+\.php$/';

    private RouteLoadingOptions $options;

    /**
     * @var string[]
     */
    private array $route_directories;

    /**
     * @var string[]
     */
    private array $api_route_directories;

    /**
     * @param string[] $route_directories
     * @param string[] $api_route_directories
     * @param RouteLoadingOptions $options
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
        foreach ($files as $file) {
            $name = $file->getFilenameWithoutExtension();

            // Make sure that the frontend.php file is always loaded last
            // because users are expected to register the fallback route there.
            if (self::FRONTEND_ROUTE_FILENAME === $name) {
                $frontend_routes = $file;
                continue;
            }

            $attributes = $this->options->getRouteAttributes($name);

            /** @var Closure(WebRoutingConfigurator) $closure */
            $closure = $this->requireFile($file, $attributes, self::ADMIN_ROUTE_FILENAME === $name);

            $configurator->group($closure, $attributes);
        }

        if ($frontend_routes) {
            $attributes = $this->options->getRouteAttributes(self::FRONTEND_ROUTE_FILENAME);
            $closure = $this->requireFile($frontend_routes, $attributes);
            /** @var Closure(WebRoutingConfigurator) $closure */
            $configurator->group($closure, $attributes);
        }
    }

    public function loadAdminRoutes(AdminRoutingConfigurator $configurator): void
    {
        $finder = $this->getFiles($this->route_directories);
        foreach ($finder as $file) {
            $name = $file->getFilenameWithoutExtension();

            if (self::ADMIN_ROUTE_FILENAME !== $name) {
                continue;
            }

            $attributes = $this->options->getRouteAttributes($name);

            $closure = $this->requireFile($file, $attributes, true);

            /** @var Closure(AdminRoutingConfigurator) $closure */
            $configurator->group($closure, $attributes);
        }
    }

    private function loadApiRoutesIn(WebRoutingConfigurator $configurator): void
    {
        foreach ($this->getFiles($this->api_route_directories) as $file) {
            $name = $file->getFilenameWithoutExtension();

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

            $closure = $this->requireFile($file, $attributes);

            /** @var Closure(WebRoutingConfigurator) $closure */
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
     * @psalm-suppress UnresolvableInclude
     *
     */
    private function requireFile(SplFileInfo $file, array $attributes = [], bool $is_admin_file = false): Closure
    {
        $this->validateAttributes($attributes);

        $closure = require $file;

        Assert::isInstanceOf(
            $closure,
            Closure::class,
            "Route file [{$file->getRealPath()}] did not return a closure."
        );

        $this->validateClosureTypeHint(
            $closure,
            $file->getRealPath(),
            $is_admin_file
        );

        return $closure;
    }

    /**
     * @param string[] $route_directories
     * @return SplFileInfo[]
     */
    private function getFiles(array $route_directories): array
    {
        if (empty($route_directories)) {
            return [];
        }

        $finder = new Finder();
        $finder->in($route_directories)
            ->depth(0)
            ->files()
            ->name(self::SEARCH_PATTERN);

        return iterator_to_array($finder);
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
                    Assert::isArray(
                        $value,
                        'Middleware for route-loading options has to be an array of strings.'
                    );
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
                        sprintf(
                            '[%s] has to be a non-empty string.',
                            RoutingConfigurator::NAMESPACE_KEY
                        )
                    );
                    break;
                case RoutingConfigurator::NAME_KEY:
                    Assert::stringNotEmpty(
                        $value,
                        sprintf(
                            '[%s] has to be a non-empty string.',
                            RoutingConfigurator::NAME_KEY
                        )
                    );
                    break;
                default;
                    throw new InvalidArgumentException("The option [$key] is not supported.");
            }
        }
    }

    /**
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     * @throws ReflectionException
     */
    private function validateClosureTypeHint(Closure $closure, string $filepath, bool $is_admin_file = false): void
    {
        $parameters = (new ReflectionFunction($closure))->getParameters();

        $this->validateParameterCount($parameters, $filepath);

        $used_interface = $this->validateCorrectInterface($parameters[0], $filepath);

        $this->validateAdminRoutingUsage($used_interface, $is_admin_file, $filepath);
    }

    private function validateParameterCount(array $parameters, string $path): void
    {
        $count = count($parameters);

        if (1 === $count) {
            return;
        }

        if (0 === $count) {
            throw InvalidRouteClosureReturned::becauseTheRouteClosureAcceptsNoArguments($path);
        }

        throw InvalidRouteClosureReturned::becauseTheRouteClosureAcceptsMoreThanOneArguments(
            $path,
            $count
        );
    }

    private function validateCorrectInterface(ReflectionParameter $param, string $filepath): string
    {
        $type = $param->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw InvalidRouteClosureReturned::becauseTheFirstParameterIsNotTypeHinted($filepath);
        }

        $name = $type->getName();

        if (Reflection::isInterfaceString($name, RoutingConfigurator::class)) {
            return $name;
        }

        throw InvalidRouteClosureReturned::becauseTheFirstParameterIsNotTypeHinted($filepath);
    }

    /**
     * @return void
     */
    private function validateAdminRoutingUsage(string $used_interface, bool $is_admin_file, string $filepath)
    {
        if ($is_admin_file) {
            if (Reflection::isInterfaceString($used_interface, WebRoutingConfigurator::class)) {
                throw InvalidRouteClosureReturned::adminRoutesAreUsingWebRouting($filepath);
            }

            return;
        }

        if (Reflection::isInterfaceString($used_interface, AdminRoutingConfigurator::class)) {
            throw InvalidRouteClosureReturned::webRoutesAreUsingAdminRouting($filepath);
        }
    }

    /**
     * @return array{0:string, 1:?string}
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
     */
    private function parseNameAndVersion(string $filename): array
    {
        // https://regexr.com/6d3v2
        $pattern = '/^(?:\w+' . self::VERSION_FLAG . ')(\d+)$/';

        $res = preg_match($pattern, $filename, $match);

        if (1 === $res) {
            Assert::keyExists($match, 1);
            return [Str::beforeFirst($filename, self::VERSION_FLAG), $match[1]];
        }
        return [$filename, null];
    }
}