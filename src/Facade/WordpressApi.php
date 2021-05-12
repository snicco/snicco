<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Facade;

	/** @see \WPEmerge\Facade\WordpressApiMixin */
	class WordpressApi {

		public function isAdmin () :bool {

			return is_admin();

		}

		public function isAdminAjax () {

			return wp_doing_ajax();

		}

		public function homeUrl( string $path = '', string $scheme = null ) {

			return home_url($path, $scheme);

		}

		public function userId () :int  {

			return get_current_user_id();

		}

		public function isUserLoggedIn() :bool {

			return is_user_logged_in();

		}

		public function loginUrl (string $redirect_on_login_to = '', bool $force_auth = false ) : string {

			return wp_login_url($redirect_on_login_to, $force_auth);

		}

		public function currentUserCan(string $cap, ...$args) :bool {

			return current_user_can($cap, ...$args );

		}

		public function fileHeaderData( string $file, array $default_headers = [], string $context = '' ) : array {

			return get_file_data($file, $default_headers, $context);

		}

	}