<?php

declare(strict_types=1);

namespace Snicco\Core\Utils
{
    
    use Closure;
    use Snicco\Support\Arr;
    
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     *
     * @framework-only
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
    
    /**
     * Returns all traits used by a class, its parent classes and traits of their traits.
     *
     * @param  object|string  $class
     *
     * @framework-only
     * @return string[]
     */
    function classUsesRecursive($class) :array
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        
        $results = [];
        
        foreach (array_reverse(class_parents($class)) + [$class => $class] as $class) {
            $results += traitUsesRecursive($class);
        }
        
        return array_unique($results);
    }
    
    /**
     * @param  string|object  $class_or_object
     *
     * @framework-only
     */
    function isInterface($class_or_object, string $interface) :bool
    {
        $class = is_object($class_or_object)
            ? get_class($class_or_object)
            : $class_or_object;
        
        $interface_exists = interface_exists($class);
        
        if ($interface_exists && $interface === $class) {
            return true;
        }
        
        if ( ! class_exists($class) && ! $interface_exists) {
            return false;
        }
        
        $implements = (array) class_implements($class);
        
        return in_array($interface, $implements, true);
    }
    
    /**
     * Returns all traits used by a trait and its traits.
     *
     * @param  string  $trait
     *
     * @framework-only
     * @return array
     */
    function traitUsesRecursive(string $trait) :array
    {
        $traits = class_uses($trait) ? : [];
        
        foreach ($traits as $trait) {
            $traits += traitUsesRecursive($trait);
        }
        
        return $traits;
    }
    
    /**
     * Get an item from an array or object using "dot" notation.
     *
     * @param  mixed  $target
     * @param  string|array|int|null  $key
     * @param  mixed  $default
     *
     * @return mixed
     * @framework-only
     */
    function dataGet($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }
        
        $key = is_array($key) ? $key : explode('.', $key);
        
        foreach ($key as $i => $segment) {
            unset($key[$i]);
            
            if (is_null($segment)) {
                return $target;
            }
            
            if ($segment === '*') {
                if ( ! is_array($target)) {
                    return value($default);
                }
                
                $result = [];
                
                foreach ($target as $item) {
                    $result[] = dataGet($item, $key);
                }
                
                return in_array('*', $key) ? Arr::collapse($result) : $result;
            }
            
            if (Arr::accessible($target) && Arr::exists($target, $segment)) {
                $target = $target[$segment];
            }
            elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            }
            else {
                return value($default);
            }
        }
        
        return $target;
    }
}