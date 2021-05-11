<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Http;

	use Contracts\ContainerAdapter as Container;
	use WPEmerge\Contracts\ResponseInterface;
	use Throwable;
	use WPEmerge\Contracts\ResponsableInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface as ErrorHandler;
	use WPEmerge\Events\HeadersSent;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\BodySent;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Exceptions\InvalidResponseException;
	use WPEmerge\Routing\Router;
	use WPEmerge\Support\Pipeline;
	use WPEmerge\Traits\HoldsMiddlewareDefinitions;

	class HttpKernel {

		use HoldsMiddlewareDefinitions;

		/** @var \WPEmerge\Routing\Router */
		private $router;

		/** @var \WPEmerge\Contracts\ErrorHandlerInterface */
		private $error_handler;

		/** @var \Contracts\ContainerAdapter */
		private $container;

		/** @var ResponseInterface */
		private $response;

		/** @var RequestInterface */
		private $request;


		private $is_test_mode = false;

		private $is_takeover_mode = false;


		public function __construct(

			Router $router,
			Container $container,
			ErrorHandler $error_handler

		) {

			$this->router        = $router;
			$this->container     = $container;
			$this->error_handler = $error_handler;

		}


		public function handle( IncomingRequest $request_event ) : void {

			$this->error_handler->register();

			if ( $this->forceRouteMatch() ) {

				$request_event->enforceRouteMatch();

			}

			try {

				$this->syncMiddlewareToRouter();

				$this->response = $this->sendRequestThroughRouter( $request_event->request );

			}

			catch ( Throwable $exception ) {

				$this->response = $this->error_handler->transformToResponse( $exception );

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

		public function runInTestMode() : void {

			$this->is_test_mode = true;

		}

		public function runInTakeoverMode() : void {

			$this->is_takeover_mode = true;

		}

		private function sendResponse() {

			$route_matched = $this->response instanceof ResponseInterface;

			if ( ! $route_matched ) {

				return;

			}

			$this->response = $this->response->prepareForSending( $this->request );

			$this->sendHeaders();

			if ( $this->request->type() !== IncomingAdminRequest::class ) {

				$this->sendBody();

			}

		}

		private function sendHeaders() {

			$this->response->sendHeaders();

			HeadersSent::dispatch( [ $this->response, $this->request ] );


		}

		private function sendBody() {

			$this->response->sendBody();

			BodySent::dispatch( [ $this->response, $this->request ] );

		}

		/** @todo handle the case where a route matched but invalid response was returned */
		private function prepareResponse( $response ) : ?ResponseInterface {

			if ( $response instanceof ResponseInterface ) {

				return $response;

			}

			if ( is_string( $response ) ) {

				return ( new Response( $response ) )->setType( 'text/html' );

			}

			if ( is_array( $response ) ) {

				/** @todo Create dedicated JSON Response. */
				return ( new Response( $response ) );

			}

			if ( $response instanceof ResponsableInterface ) {

				return $response->toResponse();

			}

			/**
			 * @todo Decide how this should be handled in production.
			 *  500, 404 ?
			 */
			if ( $this->is_takeover_mode ) {

				throw new InvalidResponseException(
					'The response by the route action is not valid.'
				);

			}

			return $response;

		}

		/**
		 * @throws \WPEmerge\Exceptions\InvalidResponseException
		 */
		private function sendRequestThroughRouter( RequestInterface $request ) : ?ResponseInterface {


			$this->container->instance( RequestInterface::class, $request );

			$pipeline = new Pipeline( $this->container );

			$middleware = $this->withMiddleware() ? $this->middleware_groups['global'] ?? [] : [];

			$response = $pipeline->send( $request )
			                     ->through( $middleware )
			                     ->then( $this->dispatchToRouter() );

			return $this->prepareResponse( $response );


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

		private function runGlobalMiddlewareWithoutMatchingRoute() : bool {

			return $this->is_takeover_mode;

		}

		private function withMiddleware() : bool {

			return ! $this->is_test_mode && $this->runGlobalMiddlewareWithoutMatchingRoute();

		}

		private function forceRouteMatch() : bool {

			return $this->is_takeover_mode;

		}


	}
