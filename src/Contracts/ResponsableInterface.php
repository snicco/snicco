<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use Psr\Http\Message\ResponseInterface;

	interface ResponsableInterface {


		/**
		 * Convert to Psr\Http\Message\ResponseInterface.
		 *
		 */
		public function toResponse() : ResponseInterface;

	}
