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
         * @todo decide if this should return a relative or absolut path.
         * @todo refactor this when we have a dedicated URL Generator.
		 *
		 * @param  array  $arguments
		 *
		 * @return string
		 */
		public function toUrl( $arguments = [] );

	}
