<?php


	namespace Tests\stubs\Controllers\Web;

	use WPEmerge\Requests\Request;

	class WebController {

		public function handle( Request $request, $view) {


			return 'web_controller';

		}

	}