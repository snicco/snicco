<?php


	declare( strict_types = 1 );


	namespace Tests\traits;;

	use Mockery;
	use WPEmerge\Facade\WP;

	trait CreateDefaultWpApiMocks {

		protected function createDefaultWpApiMocks () {

			WP::shouldReceive( 'isAdmin' )->andReturnFalse()->byDefault();
			WP::shouldReceive( 'isAdminAjax' )->andReturnFalse()->byDefault();
			WP::shouldReceive( 'fileHeaderData' )->andReturn([])->byDefault();
			WP::shouldReceive( 'wpAdminFolder' )->andReturn('wp-admin')->byDefault();
			WP::shouldReceive( 'ajaxUrl' )->andReturn('wp-admin/admin-ajax.php')->byDefault();
			WP::shouldReceive( 'adminUrl' )->andReturnUsing(function (string $path ) {

               return trim(SITE_URL, '/') . DS .  'wp-admin' . DS . $path;

            })->byDefault();
            WP::shouldReceive('homeUrl')->andReturnUsing(function (string $path ) {

                $host = SITE_URL;
                return trim($host, '/') . '/' . trim($path, '/');

            });
		}


	}