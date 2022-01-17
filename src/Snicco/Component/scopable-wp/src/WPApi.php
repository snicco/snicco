<?php

declare(strict_types=1);

namespace Snicco\Component\ScopableWP;

use InvalidArgumentException;

use function ucwords;
use function sprintf;
use function do_action;
use function strtolower;
use function add_filter;
use function add_action;
use function ctype_lower;
use function preg_replace;
use function wp_cache_get;
use function wp_cache_set;
use function trigger_error;
use function apply_filters;
use function wp_cache_incr;
use function wp_cache_decr;
use function function_exists;
use function wp_cache_delete;
use function call_user_func_array;

use const E_USER_NOTICE;

/**
 * Extend this class in your code to and add only the methods that your reference in your code.
 * ALWAYS call WordPress code through this class and your plugin will be scopable with ease.
 *
 * @api
 */
class WPApi
{
    
    const VERSION = '1.0.0';
    
    private static array $snake_cache = [];
    
    public function __call($name, $arguments)
    {
        $method = $this->methodNameToSnakeCase($name);
        $method = '\\'.$method;
        
        if ( ! function_exists($method)) {
            throw new InvalidArgumentException(
                sprintf("Called undefined WordPress function [%s].", $name)
            );
        }
        
        $this->triggerNotice($name, $method);
        
        return call_user_func_array($name, $arguments);
    }
    
    public function addFilter(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1)
    {
        return add_filter($hook_name, $callback, $priority, $accepted_args);
    }
    
    public function addAction(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1)
    {
        return add_action($hook_name, $callback, $priority, $accepted_args);
    }
    
    public function doAction(string $hook_name, ...$args) :void
    {
        do_action($hook_name, ...$args);
    }
    
    public function applyFilters(string $hook_name, $value, ...$args)
    {
        return apply_filters($hook_name, $value, ...$args);
    }
    
    public function cacheGet($key, string $group = '', $force = false, &$found = null)
    {
        return wp_cache_get($key, $group, $force, $found);
    }
    
    public function cacheSet($key, $data, string $group = '', int $expire = 0) :bool
    {
        return wp_cache_set($key, $data, $expire);
    }
    
    public function cacheDelete($key, string $group = '') :bool
    {
        return wp_cache_delete($key, $group);
    }
    
    public function cacheIncr($key, int $offset = 1, string $group = '')
    {
        return wp_cache_incr($key, $offset, $group);
    }
    
    public function cacheDecr($key, int $offset = 1, string $group = '')
    {
        return wp_cache_decr($key, $offset, $group);
    }
    
    private function methodNameToSnakeCase(string $method_name)
    {
        $key = $method_name;
        
        if (isset(self::$snake_cache[$key])) {
            return self::$snake_cache[$key];
        }
        
        if ( ! ctype_lower($method_name)) {
            $method_name = preg_replace('/\s+/u', '', ucwords($method_name));
            
            $method_name = strtolower(
                preg_replace(
                    '/(.)(?=[A-Z])/u',
                    '$1'.'_',
                    $method_name
                )
            );
        }
        
        return static::$snake_cache[$key] = $method_name;
    }
    
    private function triggerNotice(string $called_instance_method, string $proxies_to) :void
    {
        trigger_error(
            sprintf(
                "Tried to call method [%s] on [%s] but its not defined.There might be an autoload conflict.\nUsed version [%s].\nProxying to global WordPress function [%s].",
                $called_instance_method,
                self::class,
                self::VERSION,
                $proxies_to
            ),
            E_USER_NOTICE
        );
    }
    
}