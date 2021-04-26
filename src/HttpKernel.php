<?php


	namespace WPEmerge;

	use Contracts\ContainerAdapter as Container;
	use Exception;
	use Psr\Http\Message\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ResponsableInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
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

		/** @var ResponseServiceInterface */
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


		public function __construct(

			ResponseServiceInterface $response_service,
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
		 * Pages we have to send the header and body on separate hooks.
		 */
		public function sendBodyDeferred() {

			// guard against AdminBodySendable for non matching admin pages.
			if ( ! $this->response instanceof ResponseInterface ) {

				return;

			}

			$this->sendBody();

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

			if ( $this->isStrictMode() ) {

				$request->forceMatch();

			}

			$this->container->instance( RequestInterface::class, $request );

			$pipeline = new Pipeline( $this->container );

			$middleware = $this->withMiddleware() ? $this->middleware_groups['global'] : [];

			$response = $pipeline->send( $request )
			                ->through( $middleware )
			                ->then( $this->dispatchToRouter() );

			return $this->prepareResponse($response);

		}

		private function dispatchToRouter() : \Closure {

			return function ( $request ) {

				$this->container->instance( RequestInterface::class, $request );

				$this->request = $request;

				return $this->router->runRoute( $request );

			};

		}

		private function withMiddleware() : bool {

			return $this->isStrictMode() && ! $this->skipAllMiddleware();

		}

		private function isStrictMode() : bool {

			return $this->container->make( 'strict.mode' );


		}

		private function skipAllMiddleware() : bool {

			return $this->container->offsetExists( 'middleware.disable' );

		}

		private function syncMiddlewareToRouter() : void {


			$this->router->middlewarePriority( $this->middleware_priority );

			$middleware_groups = $this->middleware_groups;

			// Dont run this twice.
			if ( $this->isStrictMode() ) {

				unset( $middleware_groups['global'] );

			}

			foreach ( $middleware_groups as $key => $middleware ) {
				$this->router->middlewareGroup( $key, $middleware );
			}

			foreach ( $this->route_middleware_aliases as $key => $middleware ) {
				$this->router->aliasMiddleware( $key, $middleware );
			}

		}


	}
