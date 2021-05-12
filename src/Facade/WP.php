<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Facade;

	use WpFacade\WpFacade;

	/**
	 * @mixin \WPEmerge\Facade\WordpressApiMixin
	 */
	class WP extends WpFacade {

		protected static function getFacadeAccessor() {

			return WordpressApi::class;

		}

	}