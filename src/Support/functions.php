<?php

declare(strict_types=1);

namespace Snicco\Support\Functions
{
    
    use Closure;
    use Snicco\Support\Arr;
    
    /**
     * @internal
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     *
     * @return mixed
     */
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
    
    /**
     * Returns all traits used by a class, its parent classes and trait of their traits.
     *
     * @param  object|string  $class
     *
     * @return array
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
     * Returns all traits used by a trait and its traits.
     *
     * @param  string  $trait
     *
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