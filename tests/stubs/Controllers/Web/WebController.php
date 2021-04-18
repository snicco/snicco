<?php


	namespace Tests\stubs\Controllers\Web;

	use Tests\stubs\TestResponse;
	use WPEmerge\Requests\Request;

	class WebController {

		public function handle( Request $request, $view ) {


			return 'web_controller';

		}

		public function request( Request $request, $view ) {

			$request->body .= 'web_controller';

			return new TestResponse($request);

		}

		public function nullResponse( Request $request ) {

			$foo = 'bar';

		}

	}