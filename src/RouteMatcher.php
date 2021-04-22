<?php


	namespace WPEmerge;


	use Contracts\ContainerAdapter;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\RouteMatched;

	use WPEmerge\Contracts\HasRoutesInterface as Router;
	use WPEmerge\Events\SendBodySeparately;

	class RouteMatcher {


		/** @var \WPEmerge\Routing\Router */
		private $router;


		public function __construct( Router $router ) {

			$this->router = $router;

		}

		public function handleRequest( IncomingRequest $request_event) {

			$route = $this->router->hasMatchingRoute( $request = $request_event->request );

			if ( $route ) {

				RouteMatched::dispatch([$request]);

			}

		}

		public function sendAdminBodySeparately() {

			if ( $this->router->getCurrentRoute() ) {

				SendBodySeparately::dispatch([]);

			}

		}



	}