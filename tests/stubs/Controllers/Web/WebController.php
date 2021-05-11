<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\TestResponse;
	use WPEmerge\Http\Request;

	class WebController {

		public function handle( Request $request ) {

			$request->body = 'web_controller';

			return new TestResponse($request);

		}

		public function request( Request $request ) {

			$request->body .= '_web_controller';

			return new TestResponse($request);

		}

		public function nullResponse( Request $request) {

			$foo = 'bar';

		}

		public function assertNoView ( Request $request , string $no_view ) {

			$request->body  = $no_view;

			return new TestResponse($request);

		}


	}