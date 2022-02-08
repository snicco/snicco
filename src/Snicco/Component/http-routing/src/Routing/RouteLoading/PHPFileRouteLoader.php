<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RouteLoading;

use Closure;
use InvalidArgumentException;
use LogicException;
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


/**
 * @api
 */
final class PHPFileRouteLoader implements RouteLoader
{

    public const VERSION_FLAG = '-v';

    public const FRONTEND_ROUTE_FILENAME = 'frontend';

    public const ADMIN_ROUTE_FILENAME = 'admin';

    // Match all files that end with ".php" and don't start with an underscore.
    // https://regexr.com/691di
    private const SEARCH_PATTERN = '/^[^_].+\.php$/';

    private RoutingConfigurator $routing_configurator;

    private RouteLoadingOptions $options;

    public function __construct(RoutingConfigurator $routing_configurator, RouteLoadingOptions $options)
    {
        Assert::isInstanceOf($routing_configurator, WebRoutingConfigurator::class);
        Assert::isInstanceOf($routing_configurator, AdminRoutingConfigurator::class);
        $this->routing_configurator = $routing_configurator;
        $this->options = $options;
    }

    /**
     * @throws ReflectionException
     */
    public function loadRoutesIn(array $directories): void
    {
        $frontend_routes = null;
        $finder = $this->getFiles($directories);
        foreach ($finder as $file) {
            $name = $file->getFilenameWithoutExtension();

            // Make sure that the frontend.php file is always loaded last
            // because users are expected to register the fallback route there.
            if (self::FRONTEND_ROUTE_FILENAME === $name) {
                $frontend_routes = $file;
                continue;
            }

            $attributes = $this->options->getRouteAttributes($name);

            $this->requireFile($file, $attributes, self::ADMIN_ROUTE_FILENAME === $name);
        }

        if ($frontend_routes) {
            $attributes = $this->options->getRouteAttributes(self::FRONTEND_ROUTE_FILENAME);
            $this->requireFile($frontend_routes, $attributes);
        }
    }

    /**
     * @throws ReflectionException
     */
    public function loadApiRoutesIn(array $directories): void
    {
        foreach ($this->getFiles($directories) as $file) {
            $name = $file->getFilenameWithoutExtension();

            Assert::notSame(
                $name,
                self::FRONTEND_ROUTE_FILENAME,
                sprintf(
                    '[%s] is a reserved filename and can not be loaded as an API file.',
                    self::FRONTEND_ROUTE_FILENAME . '.php'
                )
            );

            [$name, $version] = $this->parseNameAndVersion($name);

            $attributes = $this->options->getApiRouteAttributes($name, $version);

            $this->requireFile($file, $attributes);
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
     *
     * @psalm-suppress UnresolvableInclude
     *
     */
    private function requireFile(SplFileInfo $file, array $attributes = [], bool $is_admin_file = false): void
    {
        $this->validateAttributes($attributes);

        if (!$file->isReadable()) {
            throw new LogicException(
                "Route file [{$file->getRealPath()}] is not readable."
            );
        }

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

        /** @var Closure(RoutingConfigurator):void $closure */
        $this->routing_configurator->group(
            $closure,
            $attributes
        );
    }

    private function getFiles(array $route_directories): Finder
    {
        Assert::allString($route_directories);
        Assert::allReadable($route_directories);

        $finder = new Finder();
        $finder->in($route_directories)
            ->depth(0)
            ->files()
            ->name(self::SEARCH_PATTERN);

        return $finder;
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
                        'Middleware for api options has to be an array of strings.'
                    );
                    Assert::allString(
                        $value,
                        'Middleware for api options has to be an array of strings.'
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
     * @throws ReflectionException
     *
     * @psalm-suppress PossiblyUndefinedIntArrayOffset
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

        if (Reflection::isInterface($name, RoutingConfigurator::class)) {
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
            if (Reflection::isInterface($used_interface, WebRoutingConfigurator::class)) {
                throw InvalidRouteClosureReturned::adminRoutesAreUsingWebRouting($filepath);
            }

            return;
        }

        if (Reflection::isInterface($used_interface, AdminRoutingConfigurator::class)) {
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