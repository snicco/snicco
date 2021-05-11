<?php


	declare( strict_types = 1 );


	namespace Tests\stubs\Controllers\Admin;

	use Tests\stubs\TestResponse;
	use WPEmerge\Http\Request;

	class AdminController {

		public function handle( Request $request) {

			$request->body = 'admin_controller';

			return new TestResponse($request);

		}

		public function assertNoView ( Request $request , string $no_view ) {

			$request->body  = $no_view;

			return new TestResponse($request);

		}


	}