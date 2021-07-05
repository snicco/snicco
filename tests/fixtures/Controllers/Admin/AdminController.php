<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Controllers\Admin;

	use BetterWP\Http\Psr7\Request;

	class AdminController {

		public function handle( Request $request) {

			// This controller is never run.
            // we only assert that is can be created without a FQN.


		}


	}