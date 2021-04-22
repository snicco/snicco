<?php


	namespace WPEmerge\Kernels;

	use BetterWpHooks\Traits\ListensConditionally;
	use Contracts\ContainerAdapter as Container;
	use Exception;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\HttpKernelInterface;
	use WPEmerge\Contracts\RouteInterface;
	use WPEmerge\Contracts\RouteInterface as Route;
	use WPEmerge\Contracts\ErrorHandlerInterface as ErrorHandler;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Middleware\ExecutesMiddlewareTrait;
	use WPEmerge\Middleware\HasMiddlewareDefinitionsTrait;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\ConvertsToResponseTrait;
	use WPEmerge\Responses\ResponseService;
	use WPEmerge\Contracts\HasQueryFilterInterface;
	use WPEmerge\Routing\Router;
	use WPEmerge\Routing\SortsMiddlewareTrait;
	use WPEmerge\Helpers\RoutePipeline;


	class HttpKernel implements HttpKernelInterface {

		use ListensConditionally;
		use HasMiddlewareDefinitionsTrait;
		use SortsMiddlewareTrait;
		use ConvertsToResponseTrait;
		use ExecutesMiddlewareTrait;


		/** @var \WPEmerge\Responses\ResponseService  */
		protected $response_service;

		/** @var \WPEmerge\Routing\Router  */
		protected $router;

		/** @var \WPEmerge\Contracts\ErrorHandlerInterface  */
		protected $error_handler;

		/** @var \Contracts\ContainerAdapter  */
		private $container;

		/** @var \Psr\Http\Message\ResponseInterface */
		private $response;


		public function __construct(

			ResponseService $response_service,
			Router $router,
			ErrorHandler $error_handler,
			Container $container ) {

			$this->response_service = $response_service;
			$this->router           = $router;
			$this->error_handler    = $error_handler;
			$this->container        = $container;

		}


		public function handle( RequestInterface $request ) : void {

			// whoops
			$this->error_handler->register();

			try {


				$this->response = $this->sendRequestThroughRouter( $request );


			}

			catch ( Exception $exception ) {

				$this->response = $this->error_handler->getResponse( $request , $exception );

			}

			$this->sendHeaders();

			if ( $request->type() === IncomingAdminRequest::class ) {

				return;

			}

			$this->sendBody();

			$this->error_handler->unregister();


		}

		public function sendHeaders() {

			if ( ! headers_sent() ) {

				$this->response_service->sendHeaders( $this->response );

			}

		}

		public function sendBody() {

			$this->response_service->sendBody( $this->response );

		}

		public function shouldHandle( RequestInterface $request ) : bool {

			return $this->router->hasMatchingRoute( $request ) !== null;

		}

		/**
		 * Get the route dispatcher callback.
		 *
		 * @param  \WPEmerge\Contracts\RouteInterface  $route
		 *
		 * @return \Closure
		 */
		private function dispatchToRouter( RouteInterface $route ) : \Closure {

			return function ( $request ) use ( $route ) {

				$this->container->instance( RequestInterface::class, $request );

				return $this->toResponse($route->run( $request ));

			};
		}

		private function sendRequestThroughRouter( RequestInterface $request ) {

			$this->container->instance( RequestInterface::class, $request );

			$route = $this->router->getCurrentRoute();

			$pipeline = new RoutePipeline( $this->container );

			return $pipeline->send( $request )
			                ->through( $this->gatherMiddleware() )
			                ->then( $this->dispatchToRouter( $route ) );


		}

		private function gatherMiddleware() : array {

			$middleware = array_merge( $this->applyGlobalMiddleware() );
			$middleware = $this->expandMiddleware( $middleware );
			$middleware = $this->uniqueMiddleware( $middleware );
			$middleware = $this->sortMiddleware( $middleware );

			return $middleware;
		}


		/**
		 *
		 *
		 *
		 * NOT IN USE
		 *
		 *
		 *
		 */

		public function run( Route $route ) : ResponseInterface {


			$response = $this->toResponse(

				$this->route_pipeline
					->send( $this->request )
					->through( $this->gatherMiddleware() )
					->then( $route->run() )

			);

			$response = $this->route_pipeline->send( $this->request )
			                                 ->through( $this->gatherMiddleware() )
			                                 ->then( $route->run() );

			if ( ! $response instanceof ResponseInterface ) {

				throw new ConfigurationException(

					'Response returned by the Route is not valid ' . PHP_EOL .
					'(expected ' . ResponseInterface::class . '; received ' . gettype( $response ) . ').'

				);

			}

			return $response;

		}

		protected function getResponseService() {
			// TODO: Implement getResponseService() method.
		}

		/**
		 * Register ajax action to hook into current one.
		 *
		 * @return void
		 */
		public function registerAjaxAction() {

			if ( ! wp_doing_ajax() ) {
				return;
			}

			$action = $this->request->body( 'action', $this->request->query( 'action' ) );
			$action = sanitize_text_field( $action );

			add_action( "wp_ajax_{$action}", [ $this, 'actionAjax' ] );
			add_action( "wp_ajax_nopriv_{$action}", [ $this, 'actionAjax' ] );

		}

		/**
		 * Act on ajax action.
		 *
		 * @return void
		 */
		public function actionAjax() {

			$response = $this->sendRequestThroughRouter( $this->request );

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			$this->response_service->respond( $response );

			wp_die( '', '', [ 'response' => null ] );
		}

		/**
		 * Filter the main query vars.
		 *
		 * @param  array  $query_vars
		 *
		 * @return array
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function _filterRequest( array $query_vars ) : array {

			$routes = $this->router->getRoutes();

			foreach ( $routes as $route ) {
				if ( ! $route instanceof HasQueryFilterInterface ) {
					continue;
				}

				if ( ! $route->isSatisfied( $this->request ) ) {
					continue;
				}

				$this->container[ WPEMERGE_APPLICATION_KEY ]
					->renderConfigExceptions( function () use ( $route, &$query_vars ) {

						$query_vars = $route->applyQueryFilter( $this->request, $query_vars );
					} );
				break;
			}

			return $query_vars;
		}

	}
