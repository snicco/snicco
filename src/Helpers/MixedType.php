<?php


	namespace WPEmerge\Helpers;

	class MixedType {

		/**
		 * Converts a value to an array containing this value unless it is an array.
		 * This will not convert objects like (array) casting does.
		 *
		 * @param  mixed  $argument
		 *
		 * @return array
		 */
		public static function toArray( $argument ) {

			if ( ! is_array( $argument ) ) {
				$argument = [ $argument ];
			}

			return $argument;
		}


		/**
		 * Normalize a path's slashes according to the current OS.
		 * Solves mixed slashes that are sometimes returned by WordPress core functions.
		 *
		 * @param  string  $path
		 * @param  string  $slash
		 *
		 * @return string
		 */
		public static function normalizePath( $path, $slash = DIRECTORY_SEPARATOR ) : string {

			return preg_replace( '~[' . preg_quote( '/\\', '~' ) . ']+~', $slash, $path );
		}

		/**
		 * Ensure path has a trailing slash.
		 *
		 * @param  string  $path
		 * @param  string  $slash
		 *
		 * @return string
		 */
		public static function addTrailingSlash( $path, $slash = DIRECTORY_SEPARATOR ) : string {

			$path = static::normalizePath( $path, $slash );
			$path = preg_replace( '~' . preg_quote( $slash, '~' ) . '*$~', $slash, $path );

			return $path;
		}

		/**
		 * Ensure path does not have a trailing slash.
		 *
		 * @param  string  $path
		 * @param  string  $slash
		 *
		 * @return string
		 */
		public static function removeTrailingSlash( $path, $slash = DIRECTORY_SEPARATOR ) : string {

			$path = static::normalizePath( $path, $slash );
			$path = preg_replace( '~' . preg_quote( $slash, '~' ) . '+$~', '', $path );

			return $path;
		}

	}
