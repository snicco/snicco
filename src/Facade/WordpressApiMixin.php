<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Facade;

	/**
	 * mixin class for ide support.
	 *
	 * @see \WPEmerge\Facade\WordpressApi
	 *
	 * @codeCoverageIgnore
	 */
	class WordpressApiMixin {

		private function __construct() {
		}

		/**
		 * Check if we are in the admin dashboard
		 *
		 * @return bool
		 * @see \WPEmerge\Facade\WordpressApi::isAdmin()
		 */
		public static function isAdmin() : bool {
		}

		/**
		 * Check if we are doing a request to admin-ajax.php
		 *
		 * @return bool
		 * @see \WPEmerge\Facade\WordpressApi::isAdminAjax()
		 */
		public static function isAdminAjax() : bool {
		}

		/**
		 *
		 *
		 * @param  string  $path  Optional. FilePath relative to the home URL. Default empty.
		 * @param  string|null  $scheme  Optional. Scheme to give the home URL context. Accepts
		 *                            'http', 'https', 'relative', 'rest', or null. Default null.
		 *
		 * @return string
		 * @see \WPEmerge\Facade\WordpressApi::homeUrl()
		 */
		public static function homeUrl( string $path = '', string $scheme = null ) : string {
		}

        /**
         *
         * Create a link to an admin url
         *
         * @param  string  $path
         * @param  string  $scheme
         *
         * @return string
         *
         * @see self_admin_url()
         * @see WordpressApi::adminUrl();
         *
         */
        public static function adminUrl(string $path = '', string $scheme = 'https') :string {}

		/**
		 * Get the current user's ID
		 *
		 * @return int The current user's ID, or 0 if no user is logged in.
		 * @see \WPEmerge\Facade\WordpressApi::userId()
		 */
		public static function userId() : int {
		}

		/**
		 * Determines whether the current visitor is a logged in user.
		 *
		 * @return bool True if user is logged in, false if not logged in.
		 * @see \WPEmerge\Facade\WordpressApi::isUserLoggedIn()
		 */
		public static function isUserLoggedIn() {
		}

		/**
		 * Retrieves the login URL.
		 *
		 * @param  string  $redirect_on_login_to  FilePath to redirect to on log in.
		 * @param  bool  $force_auth  Whether to force reauthorization, even if a cookie is present.
		 *                             Default false.
		 *
		 * @return string The login URL. Not HTML-encoded.
		 * @see \WPEmerge\Facade\WordpressApi::loginUrl()
		 *
		 */
		public static function loginUrl( string $redirect_on_login_to = '', bool $force_auth = false ) : string {
		}

		/**
		 * Returns whether the current user has the specified capability.
		 *
		 * Example usage:
		 *
		 *     currentUserCan( 'edit_posts' );
		 *     currentUserCan( 'edit_post', $post->ID );
		 *     currentUserCan( 'edit_post_meta', $post->ID, $meta_key );
		 *
		 * @param string $cap Capability name.
		 * @param mixed  ...$args    Optional further parameters, typically starting with an object ID.
		 * @return bool
		 * @see \WPEmerge\Facade\WordpressApi::currentUserCan()
		 * @see \current_user_can()
		 *
		 *
		 */
		public static function currentUserCan( string $cap, ...$args ) :bool {}

		/**
		 * @param string $file            Absolute path to the file.
		 * @param array  $default_headers List of headers, in the format `array( 'HeaderKey' => 'Header Name' )`.
		 * @param string $context         Optional. If specified adds filter hook {@see 'extra_$context_headers'}.
		 *
		 * @return string[]
		 * @see \get_file_data()
		 * @see \WPEmerge\Facade\WordpressApi::fileHeaderData();
		 */
		public static function fileHeaderData( string $file, array $default_headers = [], string $context = '' ) :array {}

        /**
         *
         * Create a url to a plugin menu page
         *
         * @param  string  $menu_slug
         *
         * @return string
         * @see WordpressApi::pluginPageUrl()
         * @see menu_page_url()
         */
        public static function pluginPageUrl (string $menu_slug) :string {}

        /**
         * Return the name of the admin folder.
         * Default 'wp-admin".
         *
         * @return string
         */
        public static function wpAdminFolder () :string {}

        /**
         *
         * @param  array  $keys associative array of [param => value ] pairs
         * @param  string  $url Url to append to.
         *
         * @return string
         */
        public static function addQueryArgs(array $keys, string $url ) :string {}

        /**
         *
         * Append a single query var to an URL.
         *
         * @param  string  $key parameter key
         * @param  string  $value parameter value
         * @param  string  $base_url URL to append to
         *
         * @return string
         */
        public static function addQueryArg( string $key , string $value , string $base_url ) :string {}

	}