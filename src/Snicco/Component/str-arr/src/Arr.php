<?php

/*
 * Trimmed down version of the Illuminate/Arr class with the following modifications
 * - strict type hinting
 * - final class attribute
 * - way less permissive with invalid input like null values.
 * - removal of the Collection class and substitution with ArrayObject|ArrayAccess where applicable.
 * - removal of unneeded doc-blocks
 *
 * https://github.com/laravel/framework/blob/v8.35.1/src/Illuminate/Collections/Arr.php
 *
 * License: The MIT License (MIT) https://github.com/laravel/framework/blob/v8.35.1/LICENSE.md
 *
 * Copyright (c) Taylor Otwell
 *
 */

declare(strict_types=1);

namespace Snicco\Component\StrArr;

use ArrayAccess;
use ArrayObject;
use Closure;
use InvalidArgumentException;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_merge;
use function array_rand;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function gettype;
use function is_array;
use function is_iterable;
use function is_null;
use function iterator_to_array;
use function sprintf;
use function strpos;

final class Arr
{

    /**
     * @param string|string[] $keys
     */
    public static function only(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    /**
     * Return the first element in an array passing a given truth test or the default value.
     *
     * @param mixed $default
     *
     * @return mixed
     */
    public static function first(iterable $array, ?callable $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return self::returnDefault($default);
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

        return self::returnDefault($default);
    }

    private static function returnDefault($default)
    {
        return $default instanceof Closure ? $default() : $default;
    }

    /**
     * Get one or a specified number of random values from an array.
     *
     * @return mixed
     * @throws InvalidArgumentException If the request count is greater than the number of array
     *     elements
     */
    public static function random(array $array, ?int $number = null, bool $preserve_keys = false)
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

        if ($number === 0) {
            return [];
        }

        $keys = array_rand($array, $number);

        $results = [];

        if ($preserve_keys) {
            foreach ((array)$keys as $key) {
                $results[$key] = $array[$key];
            }
        } else {
            foreach ((array)$keys as $key) {
                $results[] = $array[$key];
            }
        }

        return $results;
    }

    /**
     * Returns a modified array without the specified keys. Keys can use "dot" notation.
     *
     * @param string|string[] $keys
     */
    public static function except(array $array, $keys): array
    {
        self::forget($array, $keys);
        return $array;
    }

    /**
     * Remove one or many array items from a given array using "dot" notation.
     * Do not use this function if you $array is multidimensional and has keys that contain "."
     * themselves.
     * {@see
     * https://github.com/laravel/framework/blob/v8.35.1/tests/Support/SupportArrTest.php#L877}
     *
     * @param array $array Passed by reference
     * @param string|string[] $keys
     *
     * @see
     */
    public static function forget(array &$array, $keys): void
    {
        $original = &$array;
        $keys = Arr::toArray($keys);
        self::checkAllStringKeys($keys, 'forget');

        if (count($keys) === 0) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (self::exists($array, $key)) {
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
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    public static function toArray($value): array
    {
        return is_array($value) ? $value : [$value];
    }

    private static function checkAllStringKeys(array $keys, string $called_method): void
    {
        foreach ($keys as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException(
                    sprintf(
                        "\$keys has to be a string or an array of string when calling [%s].\nGot [%s]",
                        self::class . '::' . $called_method . '()',
                        gettype($key)
                    )
                );
            }
        }
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param ArrayAccess|array $array
     * @param string|int $key
     */
    public static function exists($array, $key): bool
    {
        self::checkIsArray($array, 'exists');
        self::checkKeyStringInt($key, 'exists');

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    private static function checkIsArray($array, string $called_method): void
    {
        if (!self::accessible($array)) {
            throw new InvalidArgumentException(
                sprintf(
                    "\$array has to be an array or instance of ArrayAccess when calling [%s].\nGot [%s]",
                    self::class . '::' . $called_method . '()',
                    gettype($array)
                )
            );
        }
    }

    /**
     * @param mixed $value
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    private static function checkKeyStringInt($key, string $called_method): void
    {
        if (!is_string($key) && !is_int($key)) {
            throw new InvalidArgumentException(
                sprintf(
                    "\$key has to be a string or an integer when calling [%s].\nGot [%s]",
                    self::class . '::' . $called_method . '()',
                    gettype($key)
                )
            );
        }
    }

    /**
     * Flattens a multi-dimensional array into a single level.
     */
    public static function flatten(iterable $array, int $depth = 50): array
    {
        $result = [];

        foreach ($array as $item) {
            $item = self::arrayItems($item);

            if (!is_array($item)) {
                $result[] = $item;
                continue;
            }
            $values = ($depth === 1)
                ? $item
                : self::flatten($item, $depth - 1);

            foreach ($values as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    private static function arrayItems($array)
    {
        if (is_array($array)) {
            return array_values($array);
        }

        if ($array instanceof ArrayObject) {
            return array_values($array->getArrayCopy());
        }

        if (is_iterable($array)) {
            return iterator_to_array($array, false);
        }

        // is there a better way?
        if ($array instanceof ArrayAccess) {
            $_r = [];
            foreach ($array as $val) {
                $_r[] = $val;
            }
            return $_r;
        }
        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param mixed $value
     */
    public static function set(array &$array, string $key, $value): array
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (count($keys) === 1) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param ArrayAccess|array $array
     * @param string|string[] $keys
     */
    public static function hasAny($array, $keys): bool
    {
        self::checkIsArray($array, 'hasAny');
        $keys = Arr::toArray($keys);
        self::checkAllStringKeys($keys, 'hasAny');

        if ($keys === [] || $array === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (self::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an item or items exist in an array using "dot" notation.
     *
     * @param ArrayAccess|array $array
     * @param string|string[] $keys
     */
    public static function has($array, $keys): bool
    {
        self::checkIsArray($array, 'has');
        $keys = Arr::toArray($keys);
        self::checkAllStringKeys($keys, 'has');

        if ($keys === [] || $array === []) {
            return false;
        }

        foreach ($keys as $key) {
            $sub_key_array = $array;

            if (self::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (self::accessible($sub_key_array) && self::exists($sub_key_array, $segment)) {
                    $sub_key_array = $sub_key_array[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get a value from the array, and remove it.
     * This function has the same limitation as Arr::forget().
     * Check the corresponding docblock in {@see Arr::forget}
     *
     * @param mixed $default
     */
    public static function pull(array &$array, string $key, $default = null)
    {
        $value = self::get($array, $key, $default);

        self::forget($array, $key);

        return $value;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * @param ArrayAccess|array $array
     * @param string|int $key
     * @param mixed $default
     */
    public static function get($array, $key, $default = null)
    {
        self::checkIsArray($array, 'get');
        self::checkKeyStringInt($key, 'get');

        if (self::exists($array, $key)) {
            return $array[$key];
        }

        if (false === strpos($key, '.')) {
            return $array[$key] ?? self::returnDefault($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (self::accessible($array) && self::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return self::returnDefault($default);
            }
        }

        return $array;
    }

    public static function mergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::mergeRecursive($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * @param array|ArrayAccess|object $target
     * @param string|array $key
     * @param mixed $default
     *
     * @return array|mixed
     */
    public static function dataGet($target, $key, $default = null)
    {
        if (!is_string($key) && !is_array($key)) {
            throw new InvalidArgumentException(
                sprintf('$key has to be string or array. Got [%s]', gettype($key))
            );
        }

        $key = is_array($key) ? $key : explode('.', $key);

        foreach ($key as $i => $segment) {
            unset($key[$i]);

            if (is_null($segment)) {
                return $target;
            }

            if ($segment === '*') {
                if (!is_array($target)) {
                    return self::returnDefault($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = self::dataGet($item, $key);
                }

                return in_array('*', $key) ? self::collapse($result) : $result;
            }

            if (self::accessible($target) && self::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return self::returnDefault($default);
            }
        }

        return $target;
    }

    /**
     * Collapses an array of arrays into a single array.
     *
     * @param array<array> $array
     */
    public static function collapse(iterable $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if ($values instanceof ArrayObject) {
                $results[] = $values->getArrayCopy();
                continue;
            }

            if ($values instanceof ArrayAccess) {
                $_r = [];
                foreach ($values as $key => $value) {
                    $_r[$key] = $value;
                }
                $results[] = $_r;
                continue;
            }

            if (!is_array($values)) {
                continue;
            }

            $results[] = $values;
        }

        return array_merge([], ...$results);
    }

}