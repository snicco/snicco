<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

trait GathersMiddleware
{
    
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
        $middleware_in_group = $this->middleware_groups[$group];
        
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
        
        if (isset($this->middleware_groups[$middleware])) {
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
     * @throws ConfigurationException
     */
    private function expandMiddlewareAtom(string $middleware) :string
    {
        if (isset($this->route_middleware_aliases[$middleware])) {
            return $this->route_middleware_aliases[$middleware];
        }
        
        if (class_exists($middleware)) {
            return $middleware;
        }
        
        throw new ConfigurationException('Unknown middleware ['.$middleware.'] used.');
    }
    
}
