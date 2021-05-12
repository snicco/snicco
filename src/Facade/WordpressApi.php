<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Facade;

	class WordpressApi {

		public function isAdmin () :bool {

			return is_admin();

		}

		public function isAdminAjax () {

			return wp_doing_ajax();

		}

	}