<?php


	declare( strict_types = 1 );


	namespace Snicco\Events;

	use Snicco\Http\Psr7\Request;

    class IncomingRequest extends Event {


		public Request $request;
        protected bool $has_matching_route = false;

        public function __construct( Request $request) {

			$this->request = $request;

		}

        public function matchedRoute()
        {

            $this->has_matching_route = true;

        }


    }