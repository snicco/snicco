<?php


	declare( strict_types = 1 );


	namespace Tests\stubs;

	use WPEmerge\Support\Url;

	trait CreatesAdminPages {

		public function urlTo(string $name) {

			$host = Url::toRouteMatcherFormat(SITE_URL);

			return $host . '/wp-admin/admin.php?page=' . $name;

		}

	}