<?php


	namespace WPEmerge\Events;


	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Requests\Request;

	class RouteMatched extends ApplicationEvent {

		/**
		 * @var \WPEmerge\Requests\Request
		 */
		private $request;

		public function __construct(Request $request) {

			$this->request = $request;

		}

		public function payload() : Request {

			return $this->request;

		}

	}