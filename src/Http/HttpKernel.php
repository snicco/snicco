<?php


	namespace WPEmerge\Http;

	use Contracts\ContainerAdapter as Container;
	use Exception;
	use Psr\Http\Message\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ResponsableInterface;
	use WPEmerge\Contracts\ResponseServiceInterface as ResponseService;
	use WPEmerge\Contracts\ErrorHandlerInterface as ErrorHandler;
	use WPEmerge\Events\HeadersSent;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Routing\Router;
	use WPEmerge\Helpers\Pipeline;
	use WPEmerge\Traits\HoldsMiddlewareDefinitions;

	class HttpKernel {

		use HoldsMiddlewareDefinitions;

		/** @var ResponseService */
		private $response_service;

		/** @var \WPEmerge\Routing\Router */
		private $router;

		/** @var \WPEmerge\Contracts\ErrorHandlerInterface */
		private $error_handler;

		/** @var \Contracts\ContainerAdapter */
		private $container;

		/** @var \Psr\Http\Message\ResponseInterface */
		private $response;

		/** @var RequestInterface */
		private $request;

		/**
		 * @var bool
		 */
		private $caught_exception = false;

		private $is_test_mode = false;

		private $is_takeover_mode = false;


		public function __construct(

			ResponseService $response_service,
			Router $router,
			Container $container,
			ErrorHandler $error_handler
		) {

			$this->response_service = $response_service;
			$this->router           = $router;
			$this->container        = $container;
			$this->error_handler    = $error_handler;

		}


		public function handle( IncomingRequest $request_event ) : void {

			// whoops
			$this->error_handler->register();

			if ( $this->forceRouteMatch() ) {

				$request_event->enforceRouteMatch();

			}

			try {

				$this->syncMiddlewareToRouter();

				$this->response = $this->sendRequestThroughRouter( $request_event->request );

			}

			catch ( Throwable $exception ) {

				$this->response = $this->error_handler->getResponse( $request_event->request, $exception );
				$this->caught_exception = true;

			}

			$this->sendResponse();

			$this->error_handler->unregister();

		}

		/**
		 * This function needs to be public because for Wordpress Admin
		 * pages we have to send the header and body on separate hooks.
		 * ( sucks. but it is what it is )
		 */
		public function sendBodyDeferred() {

			// guard against AdminBodySendable for non matching admin pages.
			if ( ! $this->response instanceof ResponseInterface ) {

				return;

			}

			$this->sendBody();

		}

		public function runInTestMode() :void {

			$this->is_test_mode = true;

		}

		public function runInTakeoverMode() :void {

			$this->is_takeover_mode = true;

		}

		private function sendResponse() {

			$route_matched = $this->response instanceof ResponseInterface;

			if ( ! $route_matched ) {

				return;

			}

			$this->sendHeaders();

			if ( $this->request->type() !== IncomingAdminRequest::class ) {

				$this->sendBody();

			}


		}

		private function sendHeaders() {

			$this->response_service->sendHeaders( $this->response );

			HeadersSent::dispatchUnless( $this->caught_exception , [ $this->response, $this->request ] );


		}

		private function sendBody() {

			$this->response_service->sendBody( $this->response );

			BodySent::dispatchUnless( $this->caught_exception, [ $this->response, $this->request ] );

		}

		private function prepareResponse( $response ) {

			if ( $response instanceof ResponseInterface ) {

				return $response;

			}

			if ( is_string( $response ) ) {

				return $this->response_service->output( $response );

			}

			if ( is_array( $response ) ) {

				return $this->response_service->json( $response );

			}

			if ( $response instanceof ResponsableInterface ) {

				return $response->toResponse();

			}

			return $response;

		}

		private function sendRequestThroughRouter( RequestInterface $request ) {


			$this->container->instance( RequestInterface::class, $request );

			$pipeline = new Pipeline( $this->container );

			$middleware = $this->withMiddleware() ? $this->middleware_groups['global'] ?? [] : [];

			$response = $pipeline->send( $request )
			                ->through( $middleware )
			                ->then( $this->dispatchToRouter() );

			return $this->prepareResponse($response);

		}

		private function dispatchToRouter() : \Closure {

			return function ( $request ) {

				$this->container->instance( RequestInterface::class, $request );

				$this->request = $request;

				if ( $this->is_test_mode ) {

					$this->router->withoutMiddleware();

				}

				return $this->router->runRoute( $request );

			};

		}

		private function syncMiddlewareToRouter() : void {


			$this->router->middlewarePriority( $this->middleware_priority );

			$middleware_groups = $this->middleware_groups;

			// Dont run global middleware in the router again.
			if ( $this->runGlobalMiddlewareWithoutMatchingRoute() ) {

				unset( $middleware_groups['global'] );

			}

			foreach ( $middleware_groups as $key => $middleware ) {

				$this->router->middlewareGroup( $key, $middleware );

			}

			foreach ( $this->route_middleware_aliases as $key => $middleware ) {

				$this->router->aliasMiddleware( $key, $middleware );

			}

		}

		private function runGlobalMiddlewareWithoutMatchingRoute () : bool {

			return $this->is_takeover_mode;

		}

		private function withMiddleware() : bool {

			return ! $this->is_test_mode && $this->runGlobalMiddlewareWithoutMatchingRoute();

		}

		private function forceRouteMatch() : bool {

			return $this->is_takeover_mode;

		}


	}
