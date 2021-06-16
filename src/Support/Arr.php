<?php


    declare(strict_types = 1);


    namespace WPEmerge\Support;

    class Arr extends \Illuminate\Support\Arr
    {

        public static function isValue($value, array $array) : bool
        {

            return array_search($value, $array, true) !== false;


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

        public static function combineFirstTwo(array $array) : array
        {

            $array = array_values($array);

            return [$array[0] => $array[1]];

        }

        public static function flattenOnePreserveKeys(array $array) : array
        {

            $flattened = is_array(static::firstEl($array)) ? static::firstEl($array) : $array;

            return $flattened;


        }

        public static function firstKey(array $array)
        {

            $array = static::wrap($array);

            return static::firstEl(array_keys($array));

        }

        public static function allAfter(array $array, int $index = 0) : array
        {

            $copy = $array;

            $array = array_values(array_slice($copy, $index));

            return $array;

        }

        public static function combineNumerical(array $merge_into, $values) : array
        {

            $merge_into = Arr::wrap($merge_into);
            $values = array_values(Arr::wrap($values));

            foreach ($values as $value) {

                $merge_into[] = $value;

            }

            return array_values(array_unique($merge_into, SORT_REGULAR));

        }

        public static function pullNextPair(&$array) : array
        {
		    $key = array_key_first($array);
		    $value = array_shift($array);
		    return [$key => $value];
        }

        public static function pullByValue($value, &$array)
        {

            $index = array_search($value, $array, true);

            if ( $index === false) {

                return null;

            }

            Arr::pull($array, $index);

        }

        public static function pullByValueReturnKey($value, &$array)
        {

            $index = array_search($value, $array, true);

            if ( ! $index) {

                return null;

            }

            Arr::pull($array, $index);

            return $index;

        }

        public static function mergeAfterValue(string $value, array $array_to_merge_into, array $array_to_merge) : array
        {

            $array_to_merge_into = array_values($array_to_merge_into);

            $index = array_search($value, $array_to_merge_into, true);

            $before = array_splice($array_to_merge_into, 0, $index + 1);
            $after = $array_to_merge_into;

            $new = array_merge($before, $array_to_merge);

            return array_merge($new, $after);


        }

        public static function removeNullRecursive(array $validate) : array
        {
            return self::walkRecursiveRemove($validate, function ($value) {

                return $value === null || $value === '';

            });
        }

        public static function walkRecursiveRemove (array $array, callable $callback) : array
        {

            foreach  ($array as $key => $value ) {

                if ( is_array($value) ) {

                    $array[$key] = call_user_func([self::class, 'walkRecursiveRemove'], $value, $callback);

                } else {

                    if ($callback($value, $key)) {

                        unset($array[$key]);

                    }
                }
            }

            return $array;
        }

        public static function mergeRecursive(array &$array1, array &$array2) : array
        {

            $merged = $array1;

            foreach ( $array2 as $key => &$value )
            {
                if ( is_array ( $value ) && isset ( $merged [$key] ) && is_array ( $merged [$key] ) )
                {
                    $merged [$key] = self::mergeRecursive ( $merged [$key], $value );
                }
                else
                {
                    $merged [$key] = $value;
                }
            }

            return $merged;

        }


    }