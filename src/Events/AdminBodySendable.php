<?php


	namespace WPEmerge\Events;

	use BetterWpHooks\Traits\DispatchesConditionally;
	use WPEmerge\Application\ApplicationEvent;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Contracts\RouteInterface;

	class AdminBodySendable extends ApplicationEvent {


		use DispatchesConditionally;

		/**
		 * @var \WPEmerge\Contracts\RequestInterface
		 */
		private $request;


		public function __construct( RequestInterface $request) {

			$this->request = $request;

		}

		public function shouldDispatch() : bool {

			return $this->request->route() instanceof RouteInterface;

		}

		public function payload()  {

			return $this->request;

		}



	}