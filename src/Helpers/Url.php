<?php


	namespace WPEmerge\Helpers;

	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Support\WPEmgereArr;
	use WPEmerge\Support\Str;

	/**
	 * A collection of tools dealing with URLs.
	 */
	class Url {

		/**
		 * Get the path for the request relative to the home url.
		 * Works only with absolute URLs.
		 *
		 * @param  RequestInterface  $request
		 * @param  string  $home_url
		 *
		 * @return string
		 *
		 * @todo allow .php ending for admin paths.
		 * @todo replace .php endings.
		 */
		public static function getPath( RequestInterface $request, $home_url = '' ) : string {

			$parsed_request = wp_parse_url( $request->getUri() );
			$parsed_home    = wp_parse_url( $home_url ? $home_url : home_url( '/' ) );

			$request_path = WPEmgereArr::get( $parsed_request, 'path', '/' );
			$request_path = static::removeTrailingSlash( $request_path );
			$request_path = static::addLeadingSlash( $request_path );


			if ( $parsed_request['host'] !== $parsed_home['host'] ) {
				return $request_path;
			}

			$home_path = WPEmgereArr::get( $parsed_home, 'path', '/' );
			$home_path = static::removeTrailingSlash( $home_path );
			$home_path = static::addLeadingSlash( $home_path );
			$path      = $request_path;

			if ( strpos( $request_path, $home_path ) === 0 ) {
				$path = substr( $request_path, strlen( $home_path ) );
			}

			return static::addLeadingSlash( $path );
		}

		/**
		 * Ensure url has a leading slash
		 *
		 * @param  string  $url
		 * @param  boolean  $leave_blank
		 *
		 * @return string
		 */
		public static function addLeadingSlash( $url, $leave_blank = false ) : string {

			if ( $leave_blank && $url === '' ) {
				return '';
			}

			return '/' . static::removeLeadingSlash( $url );
		}

		/**
		 * Ensure url does not have a leading slash
		 *
		 * @param  string  $url
		 *
		 * @return string
		 */
		public static function removeLeadingSlash( $url ) : string {

			return preg_replace( '/^\/+/', '', $url );
		}

		/**
		 * Ensure url has a trailing slash
		 *
		 * @param  string  $url
		 * @param  boolean  $leave_blank
		 *
		 * @return string
		 */
		public static function addTrailingSlash( string $url, $leave_blank = false ) : string {

			if ( $leave_blank && $url === '' ) {
				return '';
			}

			if ( Str::contains( $url, 'admin.php?' ) ) {

				return $url;

			}

			// return trailingslashit( $url );

			return rtrim( $url, '/\\' ) . '/';

		}

		/**
		 * Ensure url does not have a trailing slash
		 *
		 * @param  string  $url
		 *
		 * @return string
		 */
		public static function removeTrailingSlash( string $url ) : string {

			return untrailingslashit( $url );
		}

		public static function combinePath( $before, $new  ) : string {

			return trim( trim( $before , '/' ) . '/' . trim( $new, '/' ), '/' );

		}

	}
