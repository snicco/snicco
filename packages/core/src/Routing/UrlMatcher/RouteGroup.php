<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\UrlMatcher;

use Snicco\StrArr\Arr;
use Webmozart\Assert\Assert;
use Snicco\Core\Utils\UrlPath;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;

use function trim;
use function preg_replace;

/**
 * @interal
 */
final class RouteGroup
{
    
    private string  $namespace;
    private UrlPath $path_prefix;
    private string  $name;
    private array   $middleware;
    
    public function __construct(array $attributes = [])
    {
        $this->namespace = Arr::get($attributes, RoutingConfigurator::NAMESPACE_KEY, '');
        
        $prefix = Arr::get($attributes, RoutingConfigurator::PREFIX_KEY, '/');
        $this->path_prefix = $prefix instanceof UrlPath ? $prefix : UrlPath::fromString($prefix);
        
        $this->name = Arr::get($attributes, RoutingConfigurator::NAME_KEY, '');
        
        $middleware = Arr::wrap(Arr::get($attributes, RoutingConfigurator::MIDDLEWARE_KEY, []));
        Assert::allString($middleware);
        $this->middleware = $middleware;
    }
    
    public function mergeWith(RouteGroup $old_group) :RouteGroup
    {
        $this->middleware = $this->mergeMiddleware($old_group->middleware);
        
        $this->name = $this->mergeName($old_group->name);
        
        $this->path_prefix = $this->mergePrefix($old_group);
        
        return $this;
    }
    
    public function prefix() :UrlPath
    {
        return $this->path_prefix;
    }
    
    public function name() :string
    {
        return $this->name;
    }
    
    public function namespace() :string
    {
        return $this->namespace;
    }
    
    public function middleware() :array
    {
        return $this->middleware;
    }
    
    private function mergeMiddleware(array $old_middleware) :array
    {
        // It's important to filter for uniqueness here. Otherwise, we might add the same
        // middleware twice to a route in nested groups which will throw an exception.
        return array_unique(array_merge($old_middleware, $this->middleware));
    }
    
    private function mergeName(string $old) :string
    {
        // Remove leading and trailing dots.
        $new = preg_replace('/^\.+|\.+$/', '', $this->name);
        $old = preg_replace('/^\.+|\.+$/', '', $old);
        
        return trim($old.'.'.$new, '.');
    }
    
    private function mergePrefix(RouteGroup $old_group) :UrlPath
    {
        return $old_group->path_prefix->append($this->path_prefix);
    }
    
}