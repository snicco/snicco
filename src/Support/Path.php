<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;

	class Path {


		/**
		 * Normalize a path's slashes according to the current OS.
		 * Solves mixed slashes that are sometimes returned by WordPress core functions.
		 *
		 * @param  string  $path
		 * @param  string  $slash
		 *
		 * @return string
		 */
		public static function normalize( string $path, string $slash = DIRECTORY_SEPARATOR ) : string {

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
		public static function addTrailingSlash( $path, string $slash = DIRECTORY_SEPARATOR ) : string {

			$path = static::normalize( $path, $slash );
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
		public static function removeTrailingSlash( string $path, string $slash = DIRECTORY_SEPARATOR ) : string {

			$path = static::normalize( $path, $slash );

			return preg_replace( '~' . preg_quote( $slash, '~' ) . '+$~', '', $path );
		}

	}
