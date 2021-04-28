<?php


	namespace WPEmerge\Support;

	class Arr extends \Illuminate\Support\Arr {

		public static function isValue( $value, array $array ) : bool {

			return array_search($value,$array, true) !== false;


		}

		public static function firstEl( $array ) {

			$array = Arr::wrap($array);

			if ( empty($array) ) {

				return null;

			}

			return array_values($array)[0];

		}

		public static function combineFirstTwo( array $array ) : array {

			$array = array_values($array);

			return [$array[0] => $array[1]];

		}

	}