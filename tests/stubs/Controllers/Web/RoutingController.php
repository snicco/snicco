<?php


	namespace Tests\stubs\Controllers\Web;

	use WPEmerge\Requests\Request;

	class RoutingController {

		public function foo( Request $request ) {

			return 'foo';

		}

	}