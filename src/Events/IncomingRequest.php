<?php


	declare( strict_types = 1 );


	namespace BetterWP\Events;

	use BetterWP\Events\Event;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Support\Str;

    class IncomingRequest extends Event {


		/**
		 * @var Request
		 */
		public $request;


        /**
         * @var bool
         */
        protected $has_matching_route = false;

        public function __construct( Request $request) {

			$this->request = $request;

		}

        public function matchedRoute()
        {

            $this->has_matching_route = true;

        }


    }