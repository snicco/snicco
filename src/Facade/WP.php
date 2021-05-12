<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Facade;

	use WpFacade\WpFacade;

	/**
	 * @mixin \WPEmerge\Facade\WordpressApiMixin
	 */
	class WP extends WpFacade {

	    public const PLUGIN_PAGE_IDENTIFIER = 'wp-admin' . DIRECTORY_SEPARATOR . 'admin.php';

		protected static function getFacadeAccessor() {

			return WordpressApi::class;

		}

	}