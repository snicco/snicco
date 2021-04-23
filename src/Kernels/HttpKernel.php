<?php


	namespace WPEmerge\Kernels;

	use Contracts\ContainerAdapter as Container;
	use Exception;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\HttpKernelInterface;
	use WPEmerge\Contracts\ResponseServiceInterface;
	use WPEmerge\Contracts\ErrorHandlerInterface as ErrorHandler;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\ResponseSent;
	use WPEmerge\Middleware\ExecutesMiddlewareTrait;
	use WPEmerge\Middleware\HasMiddlewareDefinitionsTrait;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\ConvertsToResponseTrait;
	use WPEmerge\Routing\Router;
	use WPEmerge\Routing\SortsMiddlewareTrait;
	use WPEmerge\Helpers\Pipeline;
	use WPEmerge\Traits\MiddleWareDefinitions;

	class HttpKernel {

		// use HasMiddlewareDefinitionsTrait;
		// use SortsMiddlewareTrait;
		use ConvertsToResponseTrait;
		use ExecutesMiddlewareTrait;
		use MiddleWareDefinitions;

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




		public function __construct(

			ResponseServiceInterface $response_service,
			Router $router,
			ErrorHandler $error_handler,
			Container $container
		) {

			$this->response_service              = $response_service;
			$this->router                        = $router;
			$this->error_handler                 = $error_handler;
			$this->container                     = $container;


		}


		/** @todo remove conditional statement */
		public function handle( $request ) : void {

			$request = $request instanceof IncomingRequest ? $request->request : $request;

			// whoops
			$this->error_handler->register();

			try {

				$this->syncMiddlewareToRouter();

				$this->response = $this->sendRequestThroughRouter( $request );

			}

			catch ( Exception $exception ) {
				$this->response = $this->error_handler->getResponse( $request, $exception );
			}

			if ( $this->response instanceof ResponseInterface ) {

				$this->sendHeaders();

				if ( $request->type() !== IncomingAdminRequest::class ) {
					$this->sendBody();
				}

				ResponseSent::dispatch( [ $this->response, $request ] );

			}

			$this->error_handler->unregister();

		}

		private function sendHeaders() {

			$this->response_service->sendHeaders( $this->response );

		}

		/**
		 * This function needs to be public because for Wordpress Admin
		 * Pages we have to send the header and body on seperate hooks.
		 */
		public function sendBody() {

			// guard against AdminBodySendable for non matching admin pages.
			if ( ! $this->response instanceof ResponseInterface ) {

				return;

			}

			$this->response_service->sendBody( $this->response );

		}

		private function sendRequestThroughRouter( RequestInterface $request ) {

			if ( $this->isStrictMode() ) {

				$request->forceMatch();

			}

			$this->container->instance( RequestInterface::class, $request );

			$pipeline = new Pipeline( $this->container );

			$middleware = $this->withMiddleware() ? $this->middleware_groups['global'] : [];

			return $pipeline->send( $request )
			                ->through( $middleware )
			                ->then( $this->dispatchToRouter() );

		}

		/**
		 * Get the route dispatcher callback.
		 *
		 * @return \Closure
		 */
		private function dispatchToRouter() : \Closure {

			return function ( $request ) {

				$this->container->instance( RequestInterface::class, $request );

				return $this->router->runRoute( $request );

			};

		}

		private function withMiddleware() {

			return $this->isStrictMode() && ! $this->skipAllMiddleware();

		}

		private function isStrictMode() : bool {

			return $this->container->make( 'strict.mode' );


		}

		private function skipAllMiddleware() {

			return $this->container->offsetExists( 'middleware.disable' );

		}

		private function syncMiddlewareToRouter() {


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
