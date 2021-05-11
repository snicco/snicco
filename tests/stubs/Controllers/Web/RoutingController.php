<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

	use WPEmerge\Contracts\RequestInterface;

	class RoutingController {

		public function foo( RequestInterface $request ) {

			return 'foo';

		}

	}