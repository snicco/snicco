<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Request;

    class IncomingRequest extends ApplicationEvent {


		/**
		 * @var Request
		 */
		public $request;

		protected $force_route_match = false;

        /**
         * @var bool
         */
        protected $has_matching_route = false;


        public function __construct( Request $request) {

			$this->request = $request;

		}

		public function enforceRouteMatch() {

			$this->force_route_match = true;

		}

        public function matchedRoute()
        {

            $this->has_matching_route = true;

        }


    }