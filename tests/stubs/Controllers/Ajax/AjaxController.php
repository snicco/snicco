<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Ajax;

	use Tests\stubs\TestResponse;
	use WPEmerge\Http\Request;

	class AjaxController {

		public function handle( Request $request) {

			$request->body = 'ajax_controller';

			return new TestResponse($request);


		}

		public function assertNoView ( Request $request , string $no_view ) {

			$request->body  = $no_view;

			return new TestResponse($request);

		}

	}