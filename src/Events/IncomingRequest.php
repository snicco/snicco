<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Http\Request;

    class IncomingRequest extends ApplicationEvent {


		/**
		 * @var \WPEmerge\Http\Request
		 */
		public $request;

		protected $force_route_match = false;


		public function __construct( Request $request) {

			$this->request = $request;

		}

		public function enforceRouteMatch() {

			$this->force_route_match = true;

		}


	}