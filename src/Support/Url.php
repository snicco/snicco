<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;


	class Url {


		public static function combinePath( $before, $new  ) : string {

			return trim( trim( $before , '/' ) . '/' . trim( $new, '/' ), '/' ) ?: '/';

		}

		public static function toRouteMatcherFormat(string $url ) {

			return trim($url, '\\/');

		}

		public static function normalizePath( $url ) : string {

			$trimmed = trim( $url, '\\/' );

			$str = ( $trimmed ) ? '/' . $trimmed . '/' : '/';

			return $str;


		}

		public static function addTrailing( string $url ) : string {

			return rtrim($url, '/') . '/';

		}

		public static function adminPage() : string {

			$path = self::normalizePath(wp_parse_url( get_admin_url() )['path'] ?? '');

			return static::toRouteMatcherFormat($path . 'admin.php');


		}

	}
