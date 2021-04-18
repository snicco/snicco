<?php


	namespace Tests\stubs\Controllers\Admin;

	use WPEmerge\Requests\Request;

	class AdminController {

		public function handle( Request $request, $view) {


			return 'admin_controller';

		}


	}