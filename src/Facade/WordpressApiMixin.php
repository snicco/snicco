<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Facade;

	/** @codeCoverageIgnore */
	class WordpressApiMixin {

		private function __construct() {
		}

		/**
		 * Check if we are in the admin dashboard
		 *
		 * @return bool
		 */
		public static function isAdmin() : bool {
		}

		/**
		 * Check if we are doing a request to admin-ajax.php
		 *
		 * @return bool
		 */
		public static function isAdminAjax() : bool {
		}

	}