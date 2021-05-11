<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;

	class Arr extends \Illuminate\Support\Arr {

		public static function isValue( $value, array $array ) : bool {

			return array_search( $value, $array, true ) !== false;


		}

		public static function firstEl( $array ) {

			return self::nthEl($array, 0);

		}

		public static function nthEl( array $array, int $offset = 0 ) {

			$array = Arr::wrap( $array );

			if ( empty( $array ) ) {

				return null;

			}

			return array_values( $array )[$offset] ?? null;

		}

		public static function combineFirstTwo( array $array ) : array {

			$array = array_values( $array );

			return [ $array[0] => $array[1] ];

		}

		public static function flattenOnePreserveKeys( array $array ) : array {

			$flattened = is_array( static::firstEl($array) ) ? static::firstEl($array) : $array;

			return $flattened;



		}

		public static function firstKey( array $array ) {

			$array = static::wrap( $array );

			return static::firstEl( array_keys( $array ) );

		}

		public static function allAfter( array $array, int $index = 0 ) : array {

			$copy = $array;

			$array = array_values( array_slice( $copy, $index) );

			return $array;

		}

		public static function combineNumerical ( array $merge_into, $values ) : array {

			$merge_into = Arr::wrap($merge_into);
			$values = array_values(Arr::wrap($values));

			foreach ($values as $value) {

				$merge_into[] = $value;

			}

			return array_values(array_unique($merge_into, SORT_REGULAR));

		}

	}