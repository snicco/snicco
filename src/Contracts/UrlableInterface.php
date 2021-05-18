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
		 * @todo decide if this should return a relative or absolut path.
         * @todo refactor this when we have a dedicated URL Generator.
		 *
		 */
		public function toUrl( array $arguments = [] ) : string;

	}
