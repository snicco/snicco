<?php


	namespace Tests\stubs\Controllers\Ajax;

	use Tests\stubs\TestApp;
	use WPEmerge\Requests\Request;

	class AdminController {

		public function handle( Request $request, $view) {


			return 'admin_controller';

		}

	}