<?php


	namespace WPEmerge\Kernels;

	use BetterWpHooks\Traits\ListensConditionally;
	use Contracts\ContainerAdapter;
	use Exception;
	use Illuminate\Support\Arr;
	use Psr\Http\Message\ResponseInterface;
	use WPEmerge\Contracts\HttpKernelInterface;
	use WPEmerge\Contracts\RouteInterface as Route;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Events\IncomingRequest;
	use WPEmerge\Events\IncomingWebRequest;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Helpers\RoutingPipeline;
	use WPEmerge\Middleware\ExecutesMiddlewareTrait;
	use WPEmerge\Middleware\HasMiddlewareDefinitionsTrait;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\ConvertsToResponseTrait;
	use WPEmerge\Responses\ResponseService;
	use WPEmerge\Contracts\HasQueryFilterInterface;
	use WPEmerge\Routing\Router;
	use WPEmerge\Routing\SortsMiddlewareTrait;

	class HttpKernel implements HttpKernelInterface {

		use ListensConditionally;

		use HasMiddlewareDefinitionsTrait;
		use SortsMiddlewareTrait;
		use ConvertsToResponseTrait;
		use ExecutesMiddlewareTrait;

		/**
		 * Response service.
		 *
		 * @var ResponseService
		 */
		protected $response_service = null;

		/**
		 * Request.
		 *
		 * @var RequestInterface
		 */
		protected $request = null;

		/**
		 * Router.
		 *
		 * @var Router
		 */
		protected $router = null;


		/**
		 * Error handler.
		 *
		 * @var ErrorHandlerInterface
		 */
		protected $error_handler = null;

		/**
		 * Template WordPress attempted to load.
		 *
		 * @var string
		 */
		protected $template = '';

		/**
		 * @var \WPEmerge\Helpers\RoutingPipeline
		 */
		private $route_pipeline;
		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		/**
		 * @var ResponseInterface;
		 */
		private $response;

		/**
		 * Constructor.
		 *
		 * @param  RequestInterface  $request
		 * @param  ResponseService  $response_service
		 * @param  \WPEmerge\Helpers\RoutingPipeline  $route_pipeline
		 * @param  Router  $router
		 * @param  ErrorHandlerInterface  $error_handler
		 */
		public function __construct(
			RequestInterface $request,
			ResponseService $response_service,
			RoutingPipeline $route_pipeline,
			Router $router,
			ErrorHandlerInterface $error_handler,
			ContainerAdapter $container
		) {

			$this->request          = $request;
			$this->response_service = $response_service;
			$this->route_pipeline   = $route_pipeline;
			$this->router           = $router;
			$this->error_handler    = $error_handler;
			$this->container        = $container;
		}

		/**
		 * Get the current response.
		 *
		 * @return ResponseInterface|null
		 */
		private function getResponse() {

			return isset( $this->container[ WPEMERGE_RESPONSE_KEY ] ) ? $this->container[ WPEMERGE_RESPONSE_KEY ] : null;

		}


		public function run( Route $route ) {

			// whoops
			$this->error_handler->register();

			try {


				$middleware = array_merge( $this->applyGlobalMiddleware(), $route->middleware() );
				$middleware = $this->expandMiddleware( $middleware );
				$middleware = $this->uniqueMiddleware( $middleware );
				$middleware = $this->sortMiddleware( $middleware );

				$response = $this->toResponse(

					$this->route_pipeline
						->send( $this->request )
						->through( $middleware )
						->then( $route->run() )

				);

				if ( ! $response instanceof ResponseInterface ) {

					throw new ConfigurationException(

						'Response returned by the Route is not valid ' . PHP_EOL .
						'(expected ' . ResponseInterface::class . '; received ' . gettype( $response ) . ').'

					);

				}


			}
			catch ( Exception $exception ) {

				$response = $this->error_handler->getResponse( $this->request, $exception );

			}

			$this->error_handler->unregister();

			return $response;

		}


		public function _handleRequest( RequestInterface $request, $arguments = [] ) {

			$arguments = Arr::wrap( $arguments );

			$view = $arguments[0] ?? null;

			$route = $this->router->hasMatchingRoute( $request );

			if ( $route === null ) {
				return null;
			}

			$response = $this->run( $route );

			if ( $response === null ) {

				return $view;

			}

			$this->container[ WPEMERGE_RESPONSE_KEY ] = $response;

			return $response;

		}

		public function handleRequest( RequestInterface $request, $arguments = [] ) {

			$arguments = Arr::wrap( $arguments );

			$view = $arguments[0] ?? null;

			$route = $this->router->getCurrentRoute();

			$response = $this->run( $route );

			if ( $response === null ) {

				return $view;

			}

			$this->container->instance( ResponseInterface::class, $response );

			return $response;

		}

		/**
		 * Respond with the current response.
		 *
		 * @return void
		 */
		public function sendResponse() {

			$response = $this->getResponse();

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			$this->response_service->respond( $response );

		}

		public function bootstrap() {

			// Web. Use 3100 so it's high enough and has uncommonly used numbers
			// before and after. For example, 1000 is too common and it would have 999 before it
			// which is too common as well.).

			// add_filter( 'request', [ $this, 'filterRequest' ], 3100 );
			// add_filter( 'template_include', [ $this, 'filterTemplateInclude' ], 3100 );

			// Ajax.
			// add_action( 'admin_init', [ $this, 'registerAjaxAction' ] );

			// Admin.
			// add_action( 'admin_init', [ $this, 'registerAdminAction' ] );

		}

		/**
		 * Filter the main query vars.
		 *
		 * @param  array  $query_vars
		 *
		 * @return array
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function filterRequest( array $query_vars ) : array {

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

		/**
		 * Filter the main template file.
		 *
		 * @param  string  $template
		 *
		 * @return string
		 */
		public function _filterTemplateInclude( string $template ) : string {

			global $wp_query;

			$this->template = $template;

			$response = $this->handleRequest( $this->request, [ $template ] );

			// A route has matched so we use its response.
			if ( $response instanceof ResponseInterface ) {

				if ( $response->getStatusCode() === 404 ) {

					$wp_query->set_404();
				}

				add_action( 'wpemerge.kernels.http_kernel.respond', [ $this, 'sendResponse' ] );

				return WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
			}

			if ( ! empty( $composers ) ) {

				add_action( 'wpemerge.kernels.http_kernel.respond', [ $this, 'compose' ] );

				return WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
			}

			return $template;
		}

		public function filterTemplateInclude( RequestInterface $request ) : void {


			$this->container->instance( RequestInterface::class, $request );

			$this->response = $this->handleRequest( $request );

			$this->sendHeaders();

			if ( $request->type() === IncomingAdminRequest::class ) {

				return;

			}

			$this->sendBody();


		}

		public function sendHeaders() {

			if ( ! headers_sent() ) {

				$this->response_service->sendHeaders( $this->response );

			}

		}

		public function sendBody() {

			$this->response_service->sendBody( $this->response );

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

			$response = $this->handleRequest( $this->request );

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			$this->response_service->respond( $response );

			wp_die( '', '', [ 'response' => null ] );
		}

		/**
		 * Get page hook.
		 * Slightly modified version of code from wp-admin/admin.php.
		 *
		 * @return string
		 */
		private function getAdminPageHook() : string {

			global $pagenow, $typenow, $plugin_page;

			$page_hook = '';

			if ( isset( $plugin_page ) ) {
				$the_parent = $pagenow;

				if ( ! empty( $typenow ) ) {
					$the_parent = $pagenow . '?post_type=' . $typenow;
				}

				$page_hook = get_plugin_page_hook( $plugin_page, $the_parent );
			}

			return $page_hook;
		}

		/**
		 * Get admin page hook.
		 * Slightly modified version of code from wp-admin/admin.php.
		 *
		 * @param  string  $page_hook
		 *
		 * @return string
		 */
		private function getAdminHook( string $page_hook ) : string {

			global $pagenow, $plugin_page;

			if ( ! empty( $page_hook ) ) {
				return $page_hook;
			}

			if ( isset( $plugin_page ) ) {
				return $plugin_page;
			}

			if ( isset( $pagenow ) ) {
				return $pagenow;
			}

			return '';
		}

		/**
		 * Register admin action to hook into current one.
		 *
		 * @return void
		 */
		public function registerAdminAction() {

			$page_hook   = $this->getAdminPageHook();
			$hook_suffix = $this->getAdminHook( $page_hook );

			add_action( "load-{$hook_suffix}", [ $this, 'actionAdminLoad' ] );
			add_action( $hook_suffix, [ $this, 'actionAdmin' ] );

		}

		/**
		 * Act on admin action load.
		 *
		 * @return void
		 */
		public function actionAdminLoad() {

			$route = $this->router->hasMatchingRoute( $this->request );

			// $response = $this->handleRequest( $this->request );
			$response = null;

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			if ( ! headers_sent() ) {
				$this->response_service->sendHeaders( $response );
			}


		}

		/**
		 * Act on admin action.
		 *
		 * @return void
		 */
		public function actionAdmin() {

			$response = $this->getResponse();

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			$this->response_service->sendBody( $response );

		}

		public function shouldHandle( RequestInterface $request ) : bool {

			return $this->router->hasMatchingRoute( $request ) !== null;

		}


		protected function getResponseService() {
			// TODO: Implement getResponseService() method.
		}

	}
