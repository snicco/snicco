<?php

/*
 * Trimmed down version of the Illuminate/Arr class with the following modifications
 * - strict type hinting
 * - final class attribute
 * - way less permissive with invalid input like null values.
 * - removal of the Collection class and substitution with ArrayObject|ArrayAccess where applicable.
 * - removal of unneeded doc-blocks
 * - support for psalm
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
use Closure;
use InvalidArgumentException;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_rand;
use function array_shift;
use function array_values;
use function count;
use function explode;
use function gettype;
use function in_array;
use function is_array;
use function is_int;
use function is_iterable;
use function is_object;
use function is_string;
use function sprintf;
use function strpos;

class Arr
{
    /**
     * @param string|string[] $keys
     */
    public static function only(array $array, $keys): array
    {
        return array_intersect_key($array, array_flip((array) $keys));
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
        if ($condition) {
            foreach ($array as $key => $value) {
                if ($condition($value, $key)) {
                    return $value;
                }
            }
            /** @var TVal|null $default */
            return $default;
        }
        foreach ($array as $value) {
            return $value;
        }

        /** @var TVal|null $default */
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
     * @psalm-return ($number is 1 ? T : list<T>)
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
                "You requested [{$number}] items, but there are only [{$count}] items available."
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

        return $results;
    }

    /**
     * Returns a modified array without the specified keys. Keys can use "dot"
     * notation.
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
     * Do not use this function if you $array is multidimensional and has keys
     * that contain "." themselves.
     *
     * {@see https://github.com/laravel/framework/blob/v8.35.1/tests/Support/SupportArrTest.php#L877}.
     *
     * @param array           $array Passed by reference
     * @param string|string[] $keys
     *
     * @see
     */
    public static function forget(array &$array, $keys): void
    {
        $original = &$array;
        $keys = (array) $keys;
        self::checkAllStringKeys($keys, 'forget');

        if ([] === $keys) {
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

            if ([] !== $parts) {
                unset($array[array_shift($parts)]);
            }
        }
    }

    /**
     * Determine if the given key exists in the provided array.
     *
     * @param array|ArrayAccess $array
     * @param int|string        $key
     */
    public static function exists($array, $key): bool
    {
        self::checkIsArray($array, 'exists');

        if ($array instanceof ArrayAccess) {
            return $array->offsetExists($key);
        }

        return array_key_exists($key, $array);
    }

    /**
     * @psalm-assert-if-true array $value
     *
     * @param mixed $value
     */
    public static function accessible($value): bool
    {
        return is_array($value) || $value instanceof ArrayAccess;
    }

    /**
     * Flattens a multi-dimensional array into a single level.
     *
     * @return list<mixed>
     * @psalm-suppress MixedAssignment
     */
    public static function flatten(iterable $array, int $depth = 50): array
    {
        $result = [];

        foreach ($array as $item) {
            $item = is_iterable($item) ? self::arrayItems($item) : $item;

            $values = (1 === $depth)
                ? $item
                : (is_iterable($item) ? self::flatten($item, $depth - 1) : $item);

            foreach (self::toArray($values) as $value) {
                $result[] = $value;
            }
        }

        return $result;
    }

    /**
     * @template T
     *
     * @param T $value
     * @psalm-return (T is array ? T : array{0: T})
     */
    public static function toArray($value): array
    {
        return is_array($value) ? $value : [$value];
    }

    /**
     * Set an array item to a given value using "dot" notation.
     *
     * @param mixed $value
     * @psalm-suppress MixedAssignment
     */
    public static function set(array &$array, string $key, $value): array
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
            $array[array_shift($keys)] = $value;
        }

        return $array;
    }

    /**
     * Determine if any of the keys exist in an array using "dot" notation.
     *
     * @param array|ArrayAccess              $array
     * @param non-empty-array<string>|string $keys
     */
    public static function hasAny($array, $keys): bool
    {
        self::checkIsArray($array, 'hasAny');
        $keys = self::toArray($keys);
        self::checkAllStringKeys($keys, 'hasAny');

        if ([] === $array) {
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
     * @param array|ArrayAccess $array
     * @param string|string[]   $keys
     */
    public static function has($array, $keys): bool
    {
        self::checkIsArray($array, 'has');
        $keys = self::toArray($keys);
        self::checkAllStringKeys($keys, 'has');

        if ([] === $keys || [] === $array) {
            return false;
        }

        foreach ($keys as $key) {
            $sub_key_array = $array;

            if (self::exists($array, $key)) {
                continue;
            }

            foreach (explode('.', $key) as $segment) {
                if (self::accessible($sub_key_array) && self::exists($sub_key_array, $segment)) {
                    /** @var array|ArrayAccess $sub_key_array */
                    $sub_key_array = $sub_key_array[$segment];
                } else {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get a value from the array, and remove it. This function has the same
     * limitation as Arr::forget(). Check the corresponding docblock in {@see
     * Arr::forget}.
     *
     * @param mixed $default
     *
     * @return mixed
     *
     * @psalm-suppress MixedAssignment
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
     * @param array|ArrayAccess $array
     * @param int|string        $key
     * @param mixed             $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        self::checkIsArray($array, 'get');
        self::checkKeyStringInt($key, 'get');

        if (self::exists($array, $key)) {
            return $array[$key];
        }

        $key = (string) $key;

        if (false === strpos($key, '.')) {
            return $array[$key] ?? self::returnDefault($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (self::accessible($array) && self::exists($array, $segment)) {
                /** @var array|ArrayAccess $array */
                $array = $array[$segment];
            } else {
                return self::returnDefault($default);
            }
        }

        return $array;
    }

    /**
     * @psalm-suppress MixedAssignment
     */
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
     * @param array|ArrayAccess|mixed|object $target
     * @param string|string[]                $key
     * @param mixed                          $default
     *
     * @return array|mixed
     *
     * @psalm-suppress MixedAssignment
     */
    public static function dataGet($target, $key, $default = null)
    {
        self::checkAllStringKeys(self::toArray($key), 'dataGet');

        $keys = is_array($key) ? $key : explode('.', $key);

        foreach ($keys as $i => $segment) {
            unset($keys[$i]);

            if ('*' === $segment) {
                if (! is_array($target)) {
                    return self::returnDefault($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = self::dataGet($item, $keys);
                }

                return in_array('*', $keys, true) ? self::collapse($result) : $result;
            }

            if (self::accessible($target) && self::exists($target, $segment)) {
                /** @var array|ArrayAccess $target */
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
     * Collapses an array of iterables into a single array.
     *
     * @param iterable<iterable|mixed> $array Non-iterable values will be skipped
     *
     * @return list<mixed>
     *
     * @psalm-suppress MixedAssignment
     */
    public static function collapse(iterable $array): array
    {
        $results = [];

        foreach ($array as $values) {
            if (! is_iterable($values)) {
                continue;
            }
            foreach ($values as $value) {
                $results[] = $value;
            }
        }

        return $results;
    }

    private static function checkAllStringKeys(array $keys, string $called_method): void
    {
        foreach ($keys as $key) {
            if (! is_string($key)) {
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
     * @param array|ArrayAccess $array
     */
    private static function checkIsArray($array, string $called_method): void
    {
        if (! self::accessible($array)) {
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
     * @param int|string $key
     *
     * @psalm-suppress DocblockTypeContradiction
     */
    private static function checkKeyStringInt($key, string $called_method): void
    {
        if (! is_string($key) && ! is_int($key)) {
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
     * @template TVal
     * @template Default as Closure():TVal|TVal
     *
     * @param Default $default
     *
     * @return TVal
     */
    private static function returnDefault($default)
    {
        if ($default instanceof Closure) {
            $default = $default();
        }

        return $default;
    }
}
