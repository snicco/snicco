<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

    use WPEmerge\Http\Request;

    class RoutingController {

		public function foo( Request $request ) {

			return 'foo';

		}

	}