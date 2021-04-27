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

	}