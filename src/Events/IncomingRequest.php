<?php


	namespace WPEmerge\Events;

	use BetterWpHooks\Traits\DispatchesConditionally;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Requests\Request;

	class IncomingRequest extends ApplicationEvent {



		/**
		 * @var \WPEmerge\Requests\Request
		 */
		public $request;

		/** @var \WPEmerge\Routing\Router */
		private $router;

		public function __construct() {

			$this->request = Request::fromGlobals();
			$this->router = ApplicationEvent::container()->make(WPEMERGE_ROUTING_ROUTER_KEY);

		}

		public function shouldDispatch() : bool {

			return $this->router->hasMatchingRoute($this->request) !== null;

		}

	}