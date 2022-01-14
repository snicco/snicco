<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use RuntimeException;
use Snicco\StrArr\Arr;
use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Request;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Core\Middleware\Exceptions\FoundInvalidMiddleware;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;

use function Snicco\Core\Utils\isInterface;

/**
 * The middleware stack is responsible for parsing and normalized all middleware for a request.
 *
 * @internal
 */
final class MiddlewareStack
{
    
    /**
     * @api
     */
    const MIDDLEWARE_DELIMITER = ':';
    
    /**
     * @api
     */
    const ARGUMENT_SEPARATOR = ',';
    
    /**
     * @var array<string,string[]>
     */
    private const CORE_GROUPS = [
        RoutingConfigurator::FRONTEND_MIDDLEWARE => [],
        RoutingConfigurator::ADMIN_MIDDLEWARE => [],
        RoutingConfigurator::API_MIDDLEWARE => [],
        RoutingConfigurator::GLOBAL_MIDDLEWARE => [],
    ];
    
    /**
     * @var array<string,string[]>
     */
    private array $user_provided_groups = [];
    
    /**
     * @var array<string,string>
     */
    private array $route_middleware_aliases = [];
    
    /**
     * @var string[]
     */
    private array $middleware_by_increasing_priority = [];
    
    /**
     * @var string[]
     */
    private array $run_always_on_mismatch = [];
    
    private bool $middleware_disabled = false;
    
    public function __construct(array $middleware_to_always_run_on_non_route_match = [])
    {
        Assert::allString($middleware_to_always_run_on_non_route_match);
        foreach ($middleware_to_always_run_on_non_route_match as $middleware) {
            Assert::keyExists(
                self::CORE_GROUPS,
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
        
        $middleware = [RoutingConfigurator::GLOBAL_MIDDLEWARE];
        
        foreach ($route_middleware as $name) {
            if ($this->isMiddlewareGroup($name)) {
                unset($route_middleware[$name]);
                $middleware = array_merge($middleware, $this->middlewareGroups()[$name]);
            }
            else {
                $middleware[] = $name;
            }
        }
        
        return $this->create($middleware);
    }
    
    public function createForRequestWithoutRoute(Request $request) :array
    {
        return $this->create($this->middlewareForNonMatchingRequest($request));
    }
    
    public function withMiddlewareGroup(string $group, array $middlewares)
    {
        Assert::allString($middlewares);
        $this->user_provided_groups = Arr::mergeRecursive(
            $this->user_provided_groups,
            [$group => $middlewares]
        );
    }
    
    public function middlewarePriority(array $middleware_priority)
    {
        $this->middleware_by_increasing_priority = array_reverse($middleware_priority);
    }
    
    public function middlewareAliases(array $route_middleware_aliases)
    {
        $this->route_middleware_aliases = array_merge(
            $this->route_middleware_aliases,
            $route_middleware_aliases
        );
    }
    
    public function disableAllMiddleware()
    {
        $this->middleware_disabled = true;
    }
    
    /**
     * @param  array  $middleware
     *
     * @return MiddlewareBlueprint[]
     */
    private function create(array $middleware = []) :array
    {
        // Split out the global middleware since global middleware should always run first
        // independently of priority
        $prepend = [];
        if (false !== ($key = array_search(RoutingConfigurator::GLOBAL_MIDDLEWARE, $middleware))) {
            unset($middleware[$key]);
            $prepend = [RoutingConfigurator::GLOBAL_MIDDLEWARE];
        }
        
        $middleware = $this->sort(
            $this->parseAliasesAndArguments($middleware)
        );
        
        $middleware = array_merge(
            $this->parseAliasesAndArguments($prepend),
            $middleware
        );
        
        return $this->unique($middleware);
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
        
        if ($request->isToApiEndpoint()) {
            if (in_array(
                RoutingConfigurator::API_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return array_merge($middleware, [RoutingConfigurator::API_MIDDLEWARE]);
            }
            
            return $middleware;
        }
        
        if ($request->isToFrontend()) {
            if (in_array(
                RoutingConfigurator::FRONTEND_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return array_merge($middleware, [RoutingConfigurator::FRONTEND_MIDDLEWARE]);
            }
            
            return $middleware;
        }
        
        if ($request->isToAdminArea()) {
            if (in_array(
                RoutingConfigurator::ADMIN_MIDDLEWARE,
                $this->run_always_on_mismatch,
                true
            )) {
                return array_merge($middleware, [RoutingConfigurator::ADMIN_MIDDLEWARE]);
            }
            
            return $middleware;
        }
        
        return $middleware;
    }
    
    /**
     * @param  string[]  $middleware
     *
     * @return MiddlewareBlueprint[]
     * @throws FoundInvalidMiddleware
     */
    private function parseAliasesAndArguments(array $middleware) :array
    {
        $blueprints = [];
        
        foreach ($middleware as $middleware_string) {
            $pieces = explode(self::MIDDLEWARE_DELIMITER, $middleware_string, 2);
            
            if (count($pieces) > 1) {
                $blueprints[] = new MiddlewareBlueprint(
                    $this->replaceMiddlewareAlias($pieces[0]),
                    explode(self::ARGUMENT_SEPARATOR, $pieces[1])
                );
                
                continue;
            }
            
            if ($this->isMiddlewareGroup($pieces[0])) {
                $blueprints = array_merge(
                    $blueprints,
                    $this->parseAliasesAndArguments($this->middlewareGroups()[$pieces[0]])
                );
                continue;
            }
            
            $blueprints[] = new MiddlewareBlueprint(
                $this->replaceMiddlewareAlias($pieces[0])
            );
        }
        
        return $blueprints;
    }
    
    /**
     * @note Middleware with the highest priority comes first in the array
     *
     * @param  MiddlewareBlueprint[]  $middleware
     *
     * @return MiddlewareBlueprint[]
     */
    private function sort(array $middleware) :array
    {
        $sorted = $middleware;
        
        $success = usort($sorted, function ($a, $b) use ($middleware) {
            $a_priority = $this->priorityForMiddleware($a);
            $b_priority = $this->priorityForMiddleware($b);
            $diff = $b_priority - $a_priority;
            
            if ($diff !== 0) {
                return $diff;
            }
            
            // Keep relative order from original array.
            return array_search($a, $middleware) - array_search($b, $middleware);
        });
        
        if ( ! $success) {
            throw new RuntimeException("middleware could not be sorted");
        }
        
        return $sorted;
    }
    
    private function priorityForMiddleware(MiddlewareBlueprint $blueprint) :int
    {
        $priority = array_search($blueprint->class(), $this->middleware_by_increasing_priority);
        
        return $priority !== false ? (int) $priority : -1;
    }
    
    /**
     * @param  MiddlewareBlueprint[]  $middleware
     *
     * @return MiddlewareBlueprint[]
     */
    private function unique(array $middleware) :array
    {
        return array_values(array_unique($middleware, SORT_REGULAR));
    }
    
    private function replaceMiddlewareAlias(string $middleware) :string
    {
        if (isInterface($middleware, MiddlewareInterface::class)) {
            return $middleware;
        }
        
        if (isset($this->route_middleware_aliases[$middleware])) {
            $middleware = $this->route_middleware_aliases[$middleware];
            if (isInterface($middleware, MiddlewareInterface::class)) {
                return $middleware;
            }
            throw FoundInvalidMiddleware::incorrectInterface($middleware);
        }
        
        throw FoundInvalidMiddleware::becauseTheAliasDoesNotExist($middleware);
    }
    
    private function isMiddlewareGroup(string $alias_or_class) :bool
    {
        return isset($this->middlewareGroups()[$alias_or_class]);
    }
    
    /**
     * @return array<string,array>
     */
    private function middlewareGroups() :array
    {
        return array_merge(self::CORE_GROUPS, $this->user_provided_groups);
    }
    
}