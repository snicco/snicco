<?php


	namespace Tests\stubs\Controllers\Ajax;

	use WPEmerge\Requests\Request;

	class AjaxController {

		public function handle( Request $request, $view) {

			return 'ajax_controller';

		}

	}