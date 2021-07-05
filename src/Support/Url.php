<?php


	declare( strict_types = 1 );


	namespace BetterWP\Support;


	class Url {


	    // Trailing slashes are kept if present for the last segment
		public static function combineRelativePath( $before, $new  ) : string {

		    $before = ($before === '') ? '/' : '/' . trim($before, '/') . '/';

		    return $before . ltrim($new, '/');


		}

		public static function combineAbsPath($host, $path) : string
        {

            $host = rtrim($host, '/');
            $path = ltrim($path, '/');

            return $host . '/' . $path;


        }

		public static function toRouteMatcherFormat(string $url ) : string
        {

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

        public static function removeTrailing(string $path) : string
        {
            return rtrim($path, '/');
        }

        public static function rebuildQuery(string $url) : string
        {

            $parts = parse_url($url);

            if ( isset($parts['query'] ) ) {

                parse_str($parts['query'], $query);

                $parts['query'] = Arr::query($query);

            }

            return self::unParseUrl($parts);


        }


        /**
         * Stringify a url parsed with parse_url()
         */
        public static function unParseUrl(array $url) : string
        {

            $scheme = isset($url['scheme']) ? $url['scheme'].'://' : '';
            $host = $url['host'] ?? '';
            $port = isset($url['port']) ? ':'.$url['port'] : '';
            $user = $url['user'] ?? '';
            $pass = isset($url['pass']) ? ':'.$url['pass'] : '';
            $pass = ($user || $pass) ? "$pass@" : '';
            $path = $url['path'] ?? '';
            $query = isset($url['query']) ? '?'.$url['query'] : '';
            $fragment = isset($url['fragment']) ? '#'.$url['fragment'] : '';

            return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
        }


    }
