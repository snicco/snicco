<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Support\Arr;
use Snicco\Core\Routing\UrlPath;

use function trim;
use function array_merge;
use function preg_replace;

final class RouteGroup
{
    
    private string  $namespace;
    private UrlPath $path_prefix;
    private string  $name;
    private array   $middleware;
    
    public function __construct(array $attributes = [])
    {
        $this->namespace = Arr::get($attributes, 'namespace', '');
        $this->path_prefix = Arr::get($attributes, 'prefix', UrlPath::fromString('/'));
        $this->name = Arr::get($attributes, 'name', '');
        $this->middleware = Arr::get($attributes, 'middleware', []);
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
        return array_merge($old_middleware, $this->middleware);
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