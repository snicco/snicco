<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	interface ResponsableInterface {

		/**
		 * Convert to Psr\Http\Message\ResponseInterface.
		 *
         * @return mixed string|array
         *
		 */
		public function toResponsable() ;

	}
