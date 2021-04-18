<?php


	namespace WPEmerge\Kernels;

	use Contracts\ContainerAdapter;
	use Exception;
	use Psr\Http\Message\ResponseInterface;
	use WP_Query;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Contracts\HttpKernelInterface;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Contracts\ErrorHandlerInterface;
	use WPEmerge\Helpers\Handler;
	use WPEmerge\Helpers\HandlerFactory;
	use WPEmerge\Middleware\ExecutesMiddlewareTrait;
	use WPEmerge\Middleware\HasMiddlewareDefinitionsTrait;
	use WPEmerge\Middleware\ReadsHandlerMiddlewareTrait;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Responses\ConvertsToResponseTrait;
	use WPEmerge\Responses\RedirectResponse;
	use WPEmerge\Responses\ResponseService;
	use WPEmerge\Contracts\HasQueryFilterInterface;
	use WPEmerge\Routing\Router;
	use WPEmerge\Routing\SortsMiddlewareTrait;
	use WPEmerge\View\ViewService;

	class HttpKernel implements HttpKernelInterface {

		use HasMiddlewareDefinitionsTrait;
		use SortsMiddlewareTrait;
		use ConvertsToResponseTrait;
		use ReadsHandlerMiddlewareTrait;
		use ExecutesMiddlewareTrait;

		/**
		 * Container.
		 *
		 * @var ContainerAdapter
		 */
		protected $container = null;

		/**
		 * Injection factory.
		 *
		 * @var GenericFactory
		 */
		protected $factory = null;

		/**
		 * Handler factory.
		 *
		 * @var HandlerFactory
		 */
		protected $handler_factory = null;

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
		 * View Service.
		 *
		 * @var ViewService
		 */
		protected $view_service = null;

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
		 * Constructor.
		 *
		 * @param  ContainerAdapter  $container
		 * @param  GenericFactory  $factory
		 * @param  HandlerFactory  $handler_factory
		 * @param  ResponseService  $response_service
		 * @param  RequestInterface  $request
		 * @param  Router  $router
		 * @param  ViewService  $view_service
		 * @param  ErrorHandlerInterface  $error_handler
		 *
		 */
		public function __construct(
			ContainerAdapter $container,
			GenericFactory $factory,
			HandlerFactory $handler_factory,
			ResponseService $response_service,
			RequestInterface $request,
			Router $router,
			ViewService $view_service,
			ErrorHandlerInterface $error_handler
		) {

			$this->container        = $container;
			$this->factory          = $factory;
			$this->handler_factory  = $handler_factory;
			$this->response_service = $response_service;
			$this->request          = $request;
			$this->router           = $router;
			$this->view_service     = $view_service;
			$this->error_handler    = $error_handler;
		}

		/**
		 * Get the current response.
		 *
		 * @return ResponseInterface|null
		 */
		private function getResponse() {

			return isset( $this->container[ WPEMERGE_RESPONSE_KEY ] ) ? $this->container[ WPEMERGE_RESPONSE_KEY ] : null;

		}

		/**
		 * Get a Response Service instance.
		 *
		 * @return ResponseService
		 */
		private function getResponseService() {

			return $this->response_service;
		}

		/**
		 * Make a middleware class instance.
		 *
		 * @see ExecutesMiddlewareTrait::executeMiddleware()
		 *
		 * @param  string  $class
		 *
		 * @return object
		 */
		private function makeMiddleware( $class ) : object {

			return $this->factory->make( $class );

		}

		/**
		 * Execute a handler.
		 *
		 *
		 * @param  Handler  $handler
		 * @param  array  $arguments
		 *
		 * @return ResponseInterface
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		private function executeHandler( Handler $handler, $arguments = [] ) : ResponseInterface {


			$response = call_user_func_array( [ $handler, 'execute' ], $arguments );

			$response = $this->toResponse( $response );

			if ( ! $response instanceof ResponseInterface ) {
				throw new ConfigurationException(
					'Response returned by controller is not valid ' .
					'(expected ' . ResponseInterface::class . '; received ' . gettype( $response ) . ').'
				);
			}

			return $response;
		}

		public function run( RequestInterface $request, $middleware, $handler, $arguments = [] ) {

			// whoops
			$this->error_handler->register();

			try {


				$middleware = array_merge( $middleware, $this->getControllerMiddleware( $handler ) );

				$middleware = $this->expandMiddleware( $middleware );
				$middleware = $this->uniqueMiddleware( $middleware );
				$middleware = $this->sortMiddleware( $middleware );

				$response = $this->executeMiddleware( $middleware, $request, function () use ( $handler, $arguments ) {

					return $this->executeHandler( $handler, $arguments );

				} );

			}
			catch ( Exception $exception ) {
				$response = $this->error_handler->getResponse( $request, $exception );
			}

			$this->error_handler->unregister();

			return $response;
		}

		public function handleRequest( RequestInterface $request, $arguments = [] ) : ?ResponseInterface {

			$route = $this->router->execute( $request );

			if ( $route === null ) {
				return null;
			}

			$route_arguments = $route->getArguments( $request );

			$request = $request->withAttribute( 'route', $route )
			                   ->withAttribute( 'route_arguments', $route_arguments );

			$middleware = $route->getAttribute( 'middleware', [] );
			$handler    = $route->getAttribute( 'handler' );
			$arguments  = array_merge( [ $request ], $arguments, $route_arguments );

			$response = $this->run( $request, $middleware, $handler, $arguments );

			$this->container[ WPEMERGE_RESPONSE_KEY ] = $response;

			return $response;

		}

		/**
		 * Respond with the current response.
		 *
		 * @return void
		 */

		public function respond() {

			$response = $this->getResponse();

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			$this->response_service->respond( $response );
		}

		/**
		 * Output the current view outside of the routing flow.
		 *
		 * @return void
		 */
		public function compose() {

			$view = $this->view_service->make( $this->template );

			echo $view->toString();

		}

		public function bootstrap() {

			// Web. Use 3100 so it's high enough and has uncommonly used numbers
			// before and after. For example, 1000 is too common and it would have 999 before it
			// which is too common as well.).
			add_action( 'request', [ $this, 'filterRequest' ], 3100 );
			add_action( 'template_include', [ $this, 'filterTemplateInclude' ], 3100 );

			// Ajax.
			add_action( 'admin_init', [ $this, 'registerAjaxAction' ] );

			// Admin.
			add_action( 'admin_init', [ $this, 'registerAdminAction' ] );


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
		public function filterTemplateInclude( string $template ) : string {

			global $wp_query;

			$this->template = $template;

			$response = $this->handleRequest( $this->request, [ $template ] );

			// A route has matched so we use its response.
			if ( $response instanceof ResponseInterface ) {

				if ( $response->getStatusCode() === 404 ) {
					$wp_query->set_404();
				}

				add_action( 'wpemerge.kernels.http_kernel.respond', [ $this, 'respond' ] );

				return WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
			}

			// No route has matched, but we still want to compose views.
			$composers = $this->view_service->getComposersForView( $template );

			if ( ! empty( $composers ) ) {

				add_action( 'wpemerge.kernels.http_kernel.respond', [ $this, 'compose' ] );

				return WPEMERGE_DIR . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'view.php';
			}

			return $template;
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

			$response = $this->handleRequest( $this->request, [ '' ] );

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

			$response = $this->handleRequest( $this->request, [ '' ] );

			if ( ! $response instanceof ResponseInterface ) {
				return;
			}

			if ( ! headers_sent() ) {
				$this->response_service->sendHeaders( $response );
			}

			if ( $response instanceof RedirectResponse && $response->abort() ) {

				exit;

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

	}
