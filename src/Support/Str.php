<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;

	class Str extends \Illuminate\Support\Str {


		/**
		 * Get the portion of a string between two given values.
		 *
		 * First occurrence.
		 *
		 * @param  string  $subject
		 * @param  string  $from
		 * @param  string  $to
		 * @return string
		 */
		public static function firstBetweenDot(string $subject, string $from, string $to) : string {

			if ($from === '' || $to === '') {
				return $subject;
			}

			$result = static::before(static::after($subject, $from), $to);

			if ( static::contains($result, ':')) {

				return $result;

			}

			$search = $from . $result . $to;

			$new_subject = static::after($subject, $search);

			return static::firstBetweenDot( $new_subject, $from, $to);

		}

		public static function splitToKeyValuePair( string $subject, $at ) {

			[$key, $value] = explode( $at, $subject);

			return [ $key => $value];

		}



	}