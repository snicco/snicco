<?php

/*
 * This class is a derivative work of the Illuminate/Arr class with the following modifications:
 * - strict type hinting
 * - final class attribute
 * - way less permissive with invalid input like null values.
 * - removal of the Collection class and substitution with ArrayObject|ArrayAccess where applicable.
 * - removal of unneeded doc-blocks
 * - support for psalm
 *
 * The illuminate/support package is licensed under the MIT License:
 * https://github.com/laravel/framework/blob/v8.35.1/LICENSE.md
 *
 * The MIT License (MIT)
 *
 * Copyright (c) Taylor Otwell
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the 'Software'),
 * to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 */

declare(strict_types=1);

namespace Snicco\Component\StrArr;

use ArrayAccess;
use Closure;
use InvalidArgumentException;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_rand;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function is_array;
use function is_iterable;
use function is_string;
use function sprintf;
use function strpos;

/**
 * @note If the input array is multidimensional its KEYS must not contain '.' for any methods in this class
 * that allow accessing a nested array by "dot" notation.
 * {@see https://github.com/laravel/framework/issues/37318}
 */
final class Arr
{
    /**
     * @template TValue
     *
     * @param array<TValue>   $array
     * @param string|string[] $keys
     *
     * @return array<string,TValue>
     *
     * @psalm-pure
     * @psalm-suppress MixedReturnTypeCoercion
     */
    public static function only(array $array, $keys): array
    {
        $keys = array_flip((array) $keys);

        return array_intersect_key($array, $keys);
    }

    /**
     * @template TKey
     * @template TVal
     *
     * @param Closure(TVal,TKey):bool|null $condition
     * @param iterable<TKey,TVal>          $array
     * @param TVal|null                    $default
     *
     * @return TVal|null
     * @psalm-return (
     *      $default is null ? TVal|null : TVal
     * )
     */
    public static function first(iterable $array, Closure $condition = null, $default = null)
    {
        if (null !== $condition) {
            foreach ($array as $key => $value) {
                if ($condition($value, $key)) {
                    return $value;
                }
            }

            return $default;
        }

        foreach ($array as $value) {
            return $value;
        }

        return $default;
    }

    /**
     * Get one or a specified number of random values from an array.
     *
     * @template T
     *
     * @param positive-int $number
     * @param array<T>     $array
     *
     * @throws InvalidArgumentException If the requested count is greater than the number of array elements
     *
     * @return non-empty-list<T>|T
     *
     * @psalm-return ($number is 1 ? T : non-empty-list<T>)
     * @psalm-assert non-empty-array<T> $array
     */
    public static function random(array $array, int $number = 1)
    {
        $count = count($array);

        if (empty($array)) {
            throw new InvalidArgumentException('$array cant be empty.');
        }

        if ($number < 1) {
            throw new InvalidArgumentException('$number must be > 1');
        }

        if ($number > $count) {
            throw new InvalidArgumentException(
                sprintf('You requested [%d] items, but there are only [%d] items available.', $number, $count)
            );
        }

        $keys = array_rand($array, $number);

        $results = [];

        foreach ((array) $keys as $key) {
            if (1 === $number) {
                return $array[$key];
            }

            $results[] = $array[$key];
        }

        /**
         * @psalm-var non-empty-list<T>
         */
        return $results;
    }

    /**
     * @template T
     *
     * @param T $value
     *
     * @psalm-return (T is array ? T : array{0: T})
     *
     * @psalm-pure
     */
    public static function toArray($value): array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * Checks if all keys of the input array are strings.
     *
     * @psalm-param array $array
     *
     * @psalm-assert-if-true array<string,mixed> $array
     * @psalm-pure
     */
    public static function isAssoc(array $array): bool
    {
        if ([] === $array) {
            return true;
        }

        return [true] === array_unique(array_map(fn ($val): bool => is_string($val), array_keys($array)));
    }

    /**
     * Checks if the input are is a sequential list.
     *
     * @psalm-param array $array
     *
     * @psalm-assert-if-true list<TVal> $array
     * @psalm-pure
     */
    public static function isList(array $array): bool
    {
        return $array === array_values($array);
    }

    /**
     * Gets an item from an array using "." notation and returns the default
     * value if the key does not exist.
     *
     * @param array|ArrayAccess     $array
     * @param int|string            $key
     * @param mixed|Closure():mixed $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (self::keyExists($array, $key)) {
            return $array[$key];
        }

        $key = (string) $key;

        if (false === strpos($key, '.')) {
            return $array[$key] ?? self::returnDefault($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (self::accessible($array) && self::keyExists($array, $segment)) {
                /** @var mixed $array */
                $array = $array[$segment];
            } else {
                return self::returnDefault($default);
            }
        }

        return $array;
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param mixed $value
     */
    public static function set(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);

        foreach ($keys as $i => $key) {
            if (1 === count($keys)) {
                break;
            }

            unset($keys[$i]);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        if ([] !== $keys) {
            /** @psalm-suppress MixedAssignment */
            $array[array_shift($keys)] = $value;
        }
    }

    /**
     * Check if an item or items exist in an array using "." notation.
     *
     * @param array|ArrayAccess $array
     * @param int|string        $key
     */
    public static function has($array, $key): bool
    {
        if ([] === $array) {
            return false;
        }

        $key = (string) $key;

        if (self::keyExists($array, $key)) {
            return true;
        }

        $sub_key_array = $array;
        foreach (explode('.', $key) as $segment) {
            if (self::accessible($sub_key_array) && self::keyExists($sub_key_array, $segment)) {
                /** @var mixed $sub_key_array */
                $sub_key_array = $sub_key_array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if all the keys exist in an array using "dot" notation.
     *
     * @param array|ArrayAccess $array
     * @param string[]          $keys
     */
    public static function hasAll($array, array $keys): bool
    {
        if ([] === $array) {
            return false;
        }

        if ([] === $keys) {
            return false;
        }

        foreach ($keys as $key) {
            if (! self::has($array, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param array|ArrayAccess $array
     * @param string[]          $keys
     */
    public static function hasAny($array, array $keys): bool
    {
        if ([] === $array) {
            return false;
        }

        if ([] === $keys) {
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
     * @param mixed $value
     *
     * @psalm-assert-if-true array|ArrayAccess $value
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    public static function mergeRecursive(array $array1, array $array2): array
    {
        $merged = $array1;

        /**
         * @var mixed $value
         */
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::mergeRecursive($merged[$key], $value);
            } else {
                /** @psalm-suppress MixedAssignment */
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param array|ArrayAccess $array
     * @param int|string        $key
     */
    public static function keyExists($array, $key): bool
    {
        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * Collapses all iterables into a single array.
     *
     * @param iterable<iterable> $array Non-iterable values will be skipped
     *
     * @return list<mixed>
     */
    public static function collapse(iterable $array): array
    {
        $results = [];

        foreach ($array as $values) {
            /** @var mixed $value */
            foreach ($values as $value) {
                /** @psalm-suppress MixedAssignment */
                $results[] = $value;
            }
        }

        return $results;
    }

    /**
     * Flattens a multidimensional array. The resulting array contains only
     * array values.
     *
     * @return list<mixed>
     */
    public static function flatten(iterable $array, int $depth = 50): array
    {
        $result = [];

        /**
         * @var mixed $item
         */
        foreach ($array as $item) {
            /** @var mixed $item */
            $item = is_iterable($item) ? self::arrayItems($item) : $item;

            /** @var mixed $values */
            $values = (1 === $depth)
                ? $item
                : (is_iterable($item) ? self::flatten($item, $depth - 1) : $item);

            /** @var mixed $value */
            foreach (self::toArray($values) as $value) {
                /** @psalm-suppress MixedAssignment */
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * Returns a modified array without the specified keys. Keys can contain "."
     * to traverse a multidimensional arrays.
     *
     * @template TVal
     *
     * @param array<TVal>     $array
     * @param string|string[] $keys
     *
     * @return array<TVal>
     */
    public static function except(array $array, $keys): array
    {
        self::remove($array, $keys);

        return $array;
    }

    /**
     * Remove one or more array items from a given array using "dot" notation.
     *
     * @param array           $array Passed by reference
     * @param string|string[] $keys
     */
    public static function remove(array &$array, $keys): void
    {
        $original = &$array;
        $keys = self::toArray($keys);

        if ([] === $keys) {
            return;
        }

        foreach ($keys as $key) {
            // if the exact key exists in the top-level, remove it
            if (self::keyExists($array, $key)) {
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

            if ([] !== $parts) {
                unset($array[array_shift($parts)]);
            }
        }
    }

    /**
     * @return list<mixed>
     * @psalm-suppress MixedAssignment
     */
    private static function arrayItems(iterable $array): array
    {
        if (is_array($array)) {
            return array_values($array);
        }

        $res = [];
        foreach ($array as $item) {
            $res[] = $item;
        }

        return $res;
    }

    /**
     * @template TVal
     * @template Default as Closure():TVal|TVal
     *
     * @param Default $default
     *
     * @psalm-return TVal
     */
    private static function returnDefault($default)
    {
        if ($default instanceof Closure) {
            $default = $default();
        }

        return $default;
    }
}
