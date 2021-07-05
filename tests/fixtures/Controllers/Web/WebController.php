<?php


	declare( strict_types = 1 );


	namespace Tests\fixtures\Controllers\Web;

	use BetterWP\Http\Psr7\Request;

	class WebController {

		public function handle( Request $request ) {

            // This controller is never run.
            // we only assert that is can be created without a FQN.

		}



	}