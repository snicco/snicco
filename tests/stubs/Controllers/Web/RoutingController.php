<?php


	namespace Tests\stubs\Controllers\Web;

	use WPEmerge\Http\Request;

	class RoutingController {

		public function foo( Request $request ) {

			return 'foo';

		}

	}