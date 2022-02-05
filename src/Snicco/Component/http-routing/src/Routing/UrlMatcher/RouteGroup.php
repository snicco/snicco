<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\UrlMatcher;

use RuntimeException;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\UrlPath;
use Webmozart\Assert\Assert;

use function preg_replace;
use function trim;

/**
 * @interal
 */
final class RouteGroup
{

    private string $namespace;
    private UrlPath $path_prefix;
    private string $name;

    /**
     * @var string[]
     */
    private array $middleware;

    /**
     * @param array{namespace?:string, prefix?:string|UrlPath, name?:string, middleware?: string|string[]} $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->namespace = $attributes[RoutingConfigurator::NAMESPACE_KEY] ?? '';

        $prefix = $attributes[RoutingConfigurator::PREFIX_KEY] ?? '/';
        $this->path_prefix = $prefix instanceof UrlPath ? $prefix : UrlPath::fromString($prefix);

        $this->name = $attributes[RoutingConfigurator::NAME_KEY] ?? '';

        $middleware = $attributes[RoutingConfigurator::MIDDLEWARE_KEY] ?? [];
        Assert::allString($middleware);
        $this->middleware = $middleware;
    }

    public function mergeWith(RouteGroup $old_group): RouteGroup
    {
        $this->middleware = $this->mergeMiddleware($old_group->middleware);
        $this->name = $this->mergeName($old_group->name);
        $this->path_prefix = $this->mergePrefix($old_group);

        return $this;
    }

    public function prefix(): UrlPath
    {
        return $this->path_prefix;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function namespace(): string
    {
        return $this->namespace;
    }

    /** @return string[] */
    public function middleware(): array
    {
        return $this->middleware;
    }

    /**
     * @param string[] $old_middleware
     * @return string[]
     */
    private function mergeMiddleware(array $old_middleware): array
    {
        // It's important to filter for uniqueness here. Otherwise, we might add the same
        // middleware twice to a route in nested groups which will throw an exception.
        return array_unique(array_merge($old_middleware, $this->middleware));
    }

    private function mergeName(string $old): string
    {
        // Remove leading and trailing dots.
        $new = preg_replace('/^\.+|\.+$/', '', $this->name);
        $_old = preg_replace('/^\.+|\.+$/', '', $old);

        if (null === $new) {
            throw new RuntimeException("preg_replace failed for string [{$this->name}}].");
        }
        if (null === $_old) {
            throw new RuntimeException("preg_replace failed for string [$old].");
        }

        return trim($_old . '.' . $new, '.');
    }

    private function mergePrefix(RouteGroup $old_group): UrlPath
    {
        return $old_group->path_prefix->append($this->path_prefix);
    }

}