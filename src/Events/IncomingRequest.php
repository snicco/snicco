<?php


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Requests\Request;

	class IncomingRequest extends ApplicationEvent {


		/**
		 * @var \WPEmerge\Requests\Request
		 */
		public $request;

		protected $force_route_match = false;

		public function __construct() {

			$this->request = Request::fromGlobals();

			$this->request->setType(get_class($this));

		}

		public function enforceRouteMatch() {

			$this->force_route_match = true;

		}


	}