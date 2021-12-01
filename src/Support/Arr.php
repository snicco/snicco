<?php

declare(strict_types=1);

namespace Snicco\Support;

use ArrayAccess;
use InvalidArgumentException;

use function array_filter;
use function Snicco\Support\Functions\value;

use const ARRAY_FILTER_USE_KEY;

class Arr
{
    
    public static function combineFirstTwo(array $array) :array
    {
        $array = array_values($array);
        
        return [$array[0] => $array[1]];
    }
    
    public static function flattenOnePreserveKeys(array $array) :array
    {
        return is_array(static::firstEl($array)) ? static::firstEl($array) : $array;
    }
    
    public static function firstEl($array)
    {
        return self::nthEl(Arr::wrap($array), 0);
    }
    
    public static function nthEl(array $array, int $offset = 0)
    {
        $array = Arr::wrap($array);
        
        if (empty($array)) {
            return null;
        }
        
        return array_values($array)[$offset] ?? null;
    }
    
    public static function firstKey(array $array)
    {
        $array = static::wrap($array);
        
        return static::firstEl(array_keys($array));
    }
    
    public static function combineNumerical(array $merge_into, $values) :array
    {
        $merge_into = Arr::wrap($merge_into);
        $values = array_values(Arr::wrap($values));
        
        foreach ($values as $value) {
            $merge_into[] = $value;
        }
        
        return array_values(array_unique($merge_into, SORT_REGULAR));
    }
    
    public static function pullByValue($value, &$array)
    {
        $index = array_search($value, $array, true);
        
        if ($index === false) {
            return null;
        }
        
        return Arr::pull($array, $index);
    }
    
    public static function removeNullRecursive(array $validate) :array
    {
        return self::walkRecursiveRemove($validate, function ($value) {
            return $value === null || $value === '';
        });
    }
    
    public static function walkRecursiveRemove(array $array, callable $callback) :array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] =
                    call_user_func([self::class, 'walkRecursiveRemove'], $value, $callback);
            }
            else {
                if ($callback($value, $key)) {
                    unset($array[$key]);
                }
            }
        }
        
        return $array;
    }
    
    public static function mergeRecursive(array $array1, array $array2) :array
    {
        $merged = $array1;
        
        foreach ($array2 as $key => &$value) {
            if (is_array($value) && isset ($merged [$key]) && is_array($merged [$key])) {
                $merged [$key] = self::mergeRecursive($merged [$key], $value);
            }
            else {
                $merged [$key] = $value;
            }
        }
        
        return $merged;
    }
    
    public static function query($array) :string
    {
        $arr = (array_filter($array, 'is_string', ARRAY_FILTER_USE_KEY));
        
        return http_build_query($arr, '', '&', PHP_QUERY_RFC3986);
    }
    
    public static function wrap($value) :array
    {
        if (is_null($value)) {
            return [];
        }
        
        return is_array($value) ? $value : [$value];
    }
    
    /**
     * Set an array item to a given value using "dot" notation.
     * If no key is given to the method, the entire array will be replaced.
     *
     * @param  array|null  $array
     * @param  string|null  $key
     * @param  mixed  $value
     *
     * @return array
     */
    public static function set(?array &$array, ?string $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }
        
        $keys = explode('.', $key);
        
        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }
            
            unset($keys[$i]);
            
            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if ( ! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }
            
            $array = &$array[$key];
        }
        
        $array[array_shift($keys)] = $value;
        
        return $array;
    }
    
    /**
     * Get one or a specified number of random values from an array.
     *
     * @param  array  $array
     * @param  int|null  $number
     * @param  bool|false  $preserveKeys
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public static function random(array $array, $number = null, $preserveKeys = false)
    {
        $requested = is_null($number) ? 1 : $number;
        
        $count = count($array);
        
        if ($requested > $count) {
            throw new InvalidArgumentException(
                "You requested {$requested} items, but there are only {$count} items available."
            );
        }
        
        if (is_null($number)) {
            return $array[array_rand($array)];
        }
        
        if ((int) $number === 0) {
            return [];
        }
        
        $keys = array_rand($array, $number);
        
        $results = [];
        
        if ($preserveKeys) {
            foreach ((array) $keys as $key) {
                $results[$key] = $array[$key];
            }
        }
        else {
            foreach ((array) $keys as $key) {
                $results[] = $array[$key];
            }
        }
        
        return $results;
    }
    
    /**
     * Get a value from the array, and remove it.
     *
     * @param  array  $array
     * @param  string  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public static function pull(&$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);
        
        static::forget($array, $key);
        
        return $value;
    }
    
    /**
     * Get a subset of the items from the given array.
     *
     * @param  array  $array
     * @param  array|string  $keys
     *
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }
    
    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param  ArrayAccess|array  $array
     * @param  string|array  $keys
     *
     * @return bool
     */
    public static function hasAny($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }
        
        $keys = (array) $keys;
        
        if ( ! $array) {
            return false;
        }
        
        if ($keys === []) {
            return false;
        }
        
        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param  ArrayAccess|array  $array
     * @param  string|array  $keys
     *
     * @return bool
     */
    public static function has($array, $keys)
    {
        $keys = (array) $keys;
        
        if ( ! $array || $keys === []) {
            return false;
        }
        
        foreach ($keys as $key) {
            $subKeyArray = $array;
            
            if (static::exists($array, $key)) {
                continue;
            }
            
            foreach (explode('.', $key) as $segment) {
                if (static::accessible($subKeyArray) && static::exists($subKeyArray, $segment)) {
                    $subKeyArray = $subKeyArray[$segment];
                }
                else {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  ArrayAccess|array  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if ( ! static::accessible($array)) {
            return value($default);
        }
        
        if (is_null($key)) {
            return $array;
        }
        
        if (static::exists($array, $key)) {
            return $array[$key];
        }
        
        if (strpos($key, '.') === false) {
            return $array[$key] ?? value($default);
        }
        
        foreach (explode('.', $key) as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            }
            else {
                return value($default);
            }
        }
        
        return $array;
    }
    
    /**
     * Remove one or many array items from a given array using "dot" notation.
     *
     * @param  array  $array
     * @param  array|string  $keys
     *
     * @return void
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;
        
        $keys = (array) $keys;
        
        if (count($keys) === 0) {
            return;
        }
        
        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (static::exists($array, $key)) {
                unset($array[$key]);
                
                continue;
            }
            
            $parts = explode('.', $key);
            
            // clean up before each pass
            $array = &$original;
            
            while (count($parts) > 1) {
                $part = array_shift($parts);
                
                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                }
                else {
                    continue 2;
                }
            }
            
            unset($array[array_shift($parts)]);
        }
    }
    
    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param  iterable  $array
     * @param  int  $depth
     *
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
        $result = [];
        
        foreach ($array as $item) {
            if ( ! is_array($item)) {
                $result[] = $item;
            }
            else {
                $values = $depth === 1
                    ? array_values($item)
                    : static::flatten($item, $depth - 1);
                
                foreach ($values as $value) {
                    $result[] = $value;
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Return the first element in an array passing a given truth test.
     *
     * @param  iterable  $array
     * @param  callable|null  $callback
     * @param  mixed  $default
     *
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }
            
            foreach ($array as $item) {
                return $item;
            }
        }
        
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }
        
        return value($default);
    }
    
    /**
     * Determine if the given key exists in the provided array.
     *
     * @param  ArrayAccess|array  $array
     * @param  string|int  $key
     *
     * @return bool
     */
    public static function exists($array, $key)
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }
        
        return array_key_exists($key, $array);
    }
    
    /**
     * Get all of the given array except for a specified array of keys.
     *
     * @param  array  $array
     * @param  array|string  $keys
     *
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);
        
        return $array;
    }
    
    /**
     * Determine whether the given value is array accessible.
     *
     * @param  mixed  $value
     *
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }
    
    /**
     * Collapse an array of arrays into a single array.
     *
     * @param  iterable  $array
     *
     * @return array
     */
    public static function collapse($array) :array
    {
        $results = [];
        
        foreach ($array as $values) {
            if ( ! is_array($values)) {
                continue;
            }
            
            $results[] = $values;
        }
        
        return array_merge([], ...$results);
    }
    
}