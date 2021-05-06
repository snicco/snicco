<?php


	namespace Tests\stubs\Controllers\Web;

	use WPEmerge\Contracts\RequestInterface;

	class RoutingController {

		public function foo( RequestInterface $request ) {

			return 'foo';

		}

	}