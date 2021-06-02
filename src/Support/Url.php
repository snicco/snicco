<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Support;


	class Url {


	    // Trailing slashes are kept if present for the last segment
		public static function combineRelativePath( $before, $new  ) : string {

		    $before = ($before === '') ? '/' : '/' . trim($before, '/') . '/';

		    return $before . ltrim($new, '/');


			// return trim( trim( $before , '/' ) . '/' . trim( $new, '/' ), '/' ) ?: '/';

		}

		public static function combineAbsPath($host, $path) : string
        {

            $host = rtrim($host, '/');
            $path = ltrim($path, '/');

            return $host . '/' . $path;


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

        public static function addLeading(string $url) : string
        {
            return '/'. ltrim($url, '/');
        }

        public static function isValidAbsolute($path) : bool
        {
            if ( ! preg_match('~^(#|//|https?://|(mailto|tel|sms):)~', $path)) {
                return filter_var($path, FILTER_VALIDATE_URL) !== false;
            }

            return true;

        }

    }
