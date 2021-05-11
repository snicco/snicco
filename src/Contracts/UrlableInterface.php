<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	/**
	 * Interface signifying that an object can be converted to a URL.
	 */
	interface UrlableInterface {

		/**
		 * Convert to URL.
		 *
		 * @param  array  $arguments
		 *
		 * @return string
		 */
		public function toUrl( $arguments = [] );

	}
