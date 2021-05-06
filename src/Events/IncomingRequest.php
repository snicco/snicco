<?php


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Request;

	class IncomingRequest extends ApplicationEvent {


		/**
		 * @var \WPEmerge\Http\Request
		 */
		public $request;

		protected $force_route_match = false;

		/**
		 *
		 * @todo this needs to come from the container our we are bound to the implementation not interface
		 * Implement this in BetterWpHooks to allow mapped events to resolve from the container.
		 *
		 */
		public function __construct() {

			$this->request = Request::capture();

			$this->request->setType(get_class($this));

		}

		public function enforceRouteMatch() {

			$this->force_route_match = true;

		}


	}