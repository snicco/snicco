<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Closure;
use Snicco\Contracts\RouteAction;
use Snicco\Support\ReflectionDependencies;

class ClosureAction implements RouteAction
{
    
    private Closure                $resolves_to;
    private ReflectionDependencies $route_action_dependencies;
    
    public function __construct(Closure $closure, ReflectionDependencies $route_action_dependencies)
    {
        $this->resolves_to = $closure;
        $this->route_action_dependencies = $route_action_dependencies;
    }
    
    public function execute(array $args)
    {
        return call_user_func_array(
            $this->resolves_to,
            $this->route_action_dependencies->build($this->resolves_to, $args)
        );
    }
    
    public function getMiddleware() :array
    {
        return [];
    }
    
}