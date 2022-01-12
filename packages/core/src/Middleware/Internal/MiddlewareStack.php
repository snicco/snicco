<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use LogicException;
use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Core\ExceptionHandling\Exceptions\ConfigurationException;

/**
 * @internal
 */
final class MiddlewareStack
{
    
    private array $core_groups = [
        RoutingConfigurator::WEB_MIDDLEWARE => [],
        RoutingConfigurator::ADMIN_MIDDLEWARE => [],
        RoutingConfigurator::API_MIDDLEWARE => [],
        RoutingConfigurator::GLOBAL_MIDDLEWARE => [],
    ];
    
    private array $route_middleware_aliases = [];
    
    private array $middleware_priority = [];
    
    private bool $middleware_disabled = false;
    
    private array $run_always_on_mismatch = [];
    
    public function __construct(array $middleware_to_always_run_on_non_route_match = [])
    {
        Assert::allString($middleware_to_always_run_on_non_route_match);
        foreach ($middleware_to_always_run_on_non_route_match as $middleware) {
            Assert::keyExists(
                $this->core_groups,
                $middleware,
                '[%s] can not be used as middleware that is always run for non matching routes.'
            );
            $this->run_always_on_mismatch[$middleware] = $middleware;
        }
    }
    
    public function createWithRouteMiddleware(array $route_middleware) :array
    {
        if ($this->middleware_disabled) {
            return [];
        }
        
        $middleware = $this->core_groups['global'];
        
        foreach ($route_middleware as $name) {
            if (isset($this->core_groups[$name])) {
                unset($route_middleware[$name]);
                $middleware = array_merge($middleware, $this->core_groups[$name]);
            }
            else {
                $middleware[] = $name;
            }
        }
        
        $middleware = $this->expandMiddleware($middleware);
        $middleware = $this->uniqueMiddleware($middleware);
        return $this->sortMiddleware($middleware, $this->middleware_priority);
    }
    
    public function createForRequestWithoutRoute(Request $request) :array
    {
        $middleware = $this->expandMiddleware(
            $this->middlewareForNonMatchingRequest($request)
        );
        $middleware = $this->uniqueMiddleware($middleware);
        
        return $this->sortMiddleware($middleware, $this->middleware_priority);
    }
    
    public function withMiddlewareGroup(string $group, array $middlewares)
    {
        $this->core_groups[$group] = $middlewares;
    }
    
    public function middlewarePriority(array $middleware_priority)
    {
        $this->middleware_priority = $middleware_priority;
    }
    
    public function middlewareAliases(array $route_middleware_aliases)
    {
        $this->route_middleware_aliases =
            array_merge($this->route_middleware_aliases, $route_middleware_aliases);
    }
    
    public function disableAllMiddleware()
    {
        $this->middleware_disabled = true;
    }
    
    private function middlewareForNonMatchingRequest(Request $request) :array
    {
        if ($this->middleware_disabled) {
            return [];
        }
        
        $middleware = [];
        
        if (in_array(RoutingConfigurator::GLOBAL_MIDDLEWARE, $this->run_always_on_mismatch, true)) {
            $middleware = [RoutingConfigurator::GLOBAL_MIDDLEWARE];
        }
        
        if ($request->isApiEndpoint()) {
            if (in_array(
                RoutingConfigurator::API_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return $middleware + [RoutingConfigurator::API_MIDDLEWARE];
            }
            
            return $middleware;
        }
        
        if ($request->isFrontend()) {
            if (in_array(
                RoutingConfigurator::WEB_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return $middleware + [RoutingConfigurator::WEB_MIDDLEWARE];
            }
            
            return $middleware;
        }
        
        if ($request->isAdminArea()) {
            if (in_array(
                RoutingConfigurator::ADMIN_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return $middleware + [RoutingConfigurator::ADMIN_MIDDLEWARE];
            }
            
            return $middleware;
        }
        
        return $middleware;
    }
    
    /**
     * Sort array of fully qualified middleware class names by priority in ascending order.
     *
     * @param  string[]  $middleware
     * @param  array  $priority_map
     *
     * @return array
     */
    private function sortMiddleware(array $middleware, array $priority_map) :array
    {
        $sorted = $middleware;
        
        usort($sorted, function ($a, $b) use ($middleware, $priority_map) {
            $a_priority = $this->getMiddlewarePriorityForMiddleware($a, $priority_map);
            $b_priority = $this->getMiddlewarePriorityForMiddleware($b, $priority_map);
            $priority = $b_priority - $a_priority;
            
            if ($priority !== 0) {
                return $priority;
            }
            
            // Keep relative order from original array.
            return array_search($a, $middleware) - array_search($b, $middleware);
        });
        
        return array_values($sorted);
    }
    
    /**
     * Get priority for a specific middleware.
     * This is in reverse compared to definition order.
     * Middleware with unspecified priority will yield -1.
     *
     * @param  string|array  $middleware
     * @param $middleware_priority
     *
     * @return integer
     */
    private function getMiddlewarePriorityForMiddleware($middleware, $middleware_priority) :int
    {
        if (is_array($middleware)) {
            $middleware = $middleware[0];
        }
        
        $increasing_priority = array_reverse($middleware_priority);
        $priority = array_search($middleware, $increasing_priority);
        
        return $priority !== false ? (int) $priority : -1;
    }
    
    /**
     * Filter array of middleware into a unique set.
     *
     * @param  array[]  $middleware
     *
     * @return string[]
     */
    private function uniqueMiddleware(array $middleware) :array
    {
        return array_values(array_unique($middleware, SORT_REGULAR));
    }
    
    /**
     * Expand a middleware group into an array of fully qualified class names.
     *
     * @param  string  $group
     *
     * @return array[]
     * @throws ConfigurationException
     */
    private function expandMiddlewareGroup(string $group) :array
    {
        $middleware_in_group = $this->core_groups[$group];
        
        return $this->expandMiddleware($middleware_in_group);
    }
    
    /**
     * Expand array of middleware into an array of fully qualified class names.
     *
     * @param  string[]  $middleware
     *
     * @return array[]
     * @throws ConfigurationException
     */
    private function expandMiddleware(array $middleware) :array
    {
        $classes = [];
        
        foreach ($middleware as $item) {
            $classes = array_merge(
                $classes,
                $this->expandMiddlewareMolecule($item)
            );
        }
        
        return $classes;
    }
    
    /**
     * Expand middleware into an array of fully qualified class names and any companion
     * arguments.
     *
     * @param  string  $middleware
     *
     * @return array[]
     * @throws ConfigurationException
     */
    private function expandMiddlewareMolecule(string $middleware) :array
    {
        $pieces = explode(':', $middleware, 2);
        
        if (count($pieces) > 1) {
            return [
                array_merge(
                    [$this->expandMiddlewareAtom($pieces[0])],
                    explode(',', $pieces[1])
                ),
            ];
        }
        
        if (isset($this->core_groups[$middleware])) {
            return $this->expandMiddlewareGroup($middleware);
        }
        
        return [[$this->expandMiddlewareAtom($middleware)]];
    }
    
    /**
     * Expand a single middleware a fully qualified class name.
     *
     * @param  string  $middleware
     *
     * @return string
     */
    private function expandMiddlewareAtom(string $middleware) :string
    {
        if (isset($this->route_middleware_aliases[$middleware])) {
            return $this->route_middleware_aliases[$middleware];
        }
        
        if (class_exists($middleware)) {
            return $middleware;
        }
        
        throw new LogicException('Unknown middleware ['.$middleware.'] used.');
    }
    
}