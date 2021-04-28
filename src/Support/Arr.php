<?php


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

	}