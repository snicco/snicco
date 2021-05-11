<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;


	interface ResponsableInterface {


		/**
		 * Convert to Psr\Http\Message\ResponseInterface.
		 *
		 * @return ResponseInterface
		 */
		public function toResponse() : ResponseInterface;

	}
