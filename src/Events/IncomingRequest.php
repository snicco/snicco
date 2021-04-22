<?php


	namespace WPEmerge\Events;

	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Requests\Request;

	class IncomingRequest extends ApplicationEvent {


		/**
		 * @var \WPEmerge\Requests\Request
		 */
		public $request;

		public function __construct() {

			$this->request = Request::fromGlobals();

		}

		// public function shouldDispatch() : bool {
		//
		// 	return $this->router->hasMatchingRoute($this->request) !== null;
		//
		// }

	}