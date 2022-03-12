<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\RoutingConfigurator;

use Snicco\Component\HttpRouting\Routing\UrlPath;
use Snicco\Component\StrArr\Str;
use Webmozart\Assert\Assert;

use function trim;

/**
 * @psalm-immutable
 *
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class RouteGroup
{
    public string $namespace;

    public UrlPath $prefix;

    public string $name;

    /**
     * @var string[]
     */
    public array $middleware = [];

    /**
     * @param array{namespace?:string, prefix?:string|UrlPath, name?:string, middleware?: string|string[]} $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->namespace = $attributes[RoutingConfigurator::NAMESPACE_KEY] ?? '';

        $prefix = $attributes[RoutingConfigurator::PREFIX_KEY] ?? '/';
        $this->prefix = $prefix instanceof UrlPath ? $prefix : UrlPath::fromString($prefix);

        $this->name = $attributes[RoutingConfigurator::NAME_KEY] ?? '';

        $middleware = $attributes[RoutingConfigurator::MIDDLEWARE_KEY] ?? [];
        Assert::allString($middleware);
        $this->middleware = $middleware;
    }

    public function mergeWith(RouteGroup $old_group): RouteGroup
    {
        $new = clone $this;
        $new->middleware = $this->mergeMiddleware($old_group->middleware);
        $new->name = $this->mergeName($old_group->name);
        $new->prefix = $this->mergePrefix($old_group);

        return $new;
    }

    /**
     * @param string[] $old_middleware
     *
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
        $new = Str::pregReplace($this->name, '/^\.+|\.+$/', '');
        $_old = Str::pregReplace($old, '/^\.+|\.+$/', '');

        return trim($_old . '.' . $new, '.');
    }

    private function mergePrefix(RouteGroup $old_group): UrlPath
    {
        return $old_group->prefix->append($this->prefix);
    }
}
