<?php


	namespace WPEmerge\Application;

	use BetterWpHooks\Contracts\Dispatcher;
	use BetterWpHooks\Traits\BetterWpHooksFacade;
	use Closure;
	use Contracts\ContainerAdapter;
	use Psr\Http\Message\ResponseInterface;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Contracts\RequestInterface;
	use WPEmerge\Events\AdminBodySendable;
	use WPEmerge\Events\IncomingAdminRequest;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Kernels\HttpKernel;
	use WPEmerge\Requests\Request;
	use WPEmerge\Support\Arr;

	/**
	 * The core WP Emerge component representing an application.
	 */
	class Application {

		use BetterWpHooksFacade;

		use HasAliasesTrait;
		use LoadsServiceProvidersTrait;
		use HasContainerTrait;

		/**
		 * Flag whether to intercept and render configuration exceptions.
		 *
		 * @var boolean
		 */
		protected $render_config_exceptions = true;

		/**
		 * Flag whether the application has been bootstrapped.
		 *
		 * @var boolean
		 */
		protected $bootstrapped = false;


		/**
		 * Make and assign a new application instance.
		 *
		 * @param  string  $container_adapter  ::class or default
		 *
		 * @return static
		 */
		public static function create( string $container_adapter ) : Application {

			return new static(
				( $container_adapter !== 'default' ) ? $container_adapter : new BaseContainerAdapter()
			);
		}


		/**
		 * Constructor.
		 *
		 * @param  ContainerAdapter  $container
		 * @param  boolean  $render_config_exceptions
		 */
		public function __construct( ContainerAdapter $container, $render_config_exceptions = true ) {

			$this->setContainerAdapter( $container );
			$this->container()[ WPEMERGE_APPLICATION_KEY ]   = $this;
			$this->container()[ WPEMERGE_CONTAINER_ADAPTER ] = $this->container();
			$this->render_config_exceptions                  = $render_config_exceptions;


		}


		/**
		 * Get whether the application has been bootstrapped.
		 *
		 * @return boolean
		 */
		public function isBootstrapped() : bool {

			return $this->bootstrapped;
		}

		/**
		 * Bootstrap the application.
		 *
		 * @param  array  $config  The configuration provided by a user during bootstrapping.
		 * @param  boolean  $run
		 *
		 * @return void
		 * @throws ConfigurationException
		 */
		public function bootstrap( array $config = [], bool $run = true ) {

			if ( $this->isBootstrapped() ) {
				throw new ConfigurationException( static::class . ' already bootstrapped.' );
			}

			$this->bootstrapped = true;

			$container = $this->container();

			$this->loadConfig( $container, $config );

			$this->loadServiceProviders( $container );

			$this->loadEventDispatcher();

			$this->renderConfigExceptions( function () use ( $run ) {

				$this->loadRoutes();

				if ( $run ) {

					/** @var \WPEmerge\Kernels\HttpKernel $kernel */
					$kernel = $this->resolve( WPEMERGE_WORDPRESS_HTTP_KERNEL_KEY );


				}
			} );
		}

		/**
		 * Load config into the service container.
		 *
		 *
		 * @param  ContainerAdapter  $container
		 * @param  array  $config
		 *
		 * @return void
		 */
		private function loadConfig( ContainerAdapter $container, array $config ) {

			$container[ WPEMERGE_CONFIG_KEY ] = $config;

		}

		/**
		 * Load route definition files depending on the current request.
		 *
		 * @return void
		 */
		private function loadRoutes() {

			if ( wp_doing_ajax() ) {

				$this->loadRoutesGroup( 'ajax' );

				return;
			}

			if ( is_admin() ) {

				$this->loadRoutesGroup( 'admin' );

				return;
			}

			$this->loadRoutesGroup( 'web' );

		}

		/**
		 * Load a route group applying default attributes, if any.
		 *
		 *
		 * @param  string  $group
		 *
		 * @return void
		 */
		private function loadRoutesGroup( string $group ) {


			$config     = $this->resolve( WPEMERGE_CONFIG_KEY );
			$file       = Arr::get( $config, 'routes.' . $group . '.definitions', '' );
			$attributes = Arr::get( $config, 'routes.' . $group . '.attributes', [] );

			if ( empty( $file ) ) {
				return;
			}

			$middleware = Arr::get( $attributes, 'middleware', [] );

			if ( ! in_array( $group, $middleware, true ) ) {
				$middleware = array_merge( [ $group ], $middleware );
			}

			$attributes['middleware'] = $middleware;

			/** @var \WPEmerge\Routing\RouteBlueprint $blueprint */
			$blueprint = $this->resolve( WPEMERGE_ROUTING_ROUTE_BLUEPRINT_KEY );
			$blueprint->attributes( $attributes )->group( $file );


		}


		/**
		 * Catch any configuration exceptions and short-circuit to an error page.
		 *
		 *
		 * @param  Closure  $callable
		 *
		 * @return void
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function renderConfigExceptions( Closure $callable ) {

			try {

				$callable();

			}
			catch ( ConfigurationException $exception ) {
				if ( ! $this->render_config_exceptions ) {
					throw $exception;
				}

				$request = Request::fromGlobals();
				$handler = $this->resolve( WPEMERGE_EXCEPTIONS_CONFIGURATION_ERROR_HANDLER_KEY );

				add_filter( 'wpemerge.pretty_errors.apply_admin_styles', '__return_false' );

				$response_service = $this->resolve( WPEMERGE_RESPONSE_SERVICE_KEY );
				$response_service->respond( $handler->getResponse( $request, $exception ) );

				wp_die();
			}
		}


		private function loadEventDispatcher() {


			$mapped_events = dirname( __FILE__, 2 ) . DIRECTORY_SEPARATOR . 'mapped.events.php';
			$listeners     = dirname( __FILE__, 2 ) . DIRECTORY_SEPARATOR . 'event.listeners.php';

			$mapped_events = require_once $mapped_events;
			$listeners     = require_once $listeners;

			ApplicationEvent::make( $this->container() )->map( $mapped_events )
			                ->listeners( $listeners )->boot();

			ApplicationEvent::listen( 'admin_init', function () {

				if ( $hook = $this->getAdminPageHook() ) {

					ApplicationEvent::listen( 'load-' . $hook, function () {

						IncomingAdminRequest::dispatch( [] );

					} );

					ApplicationEvent::listen( $hook, function () {

						AdminBodySendable::dispatch( [
							$this->container()->make( RequestInterface::class ),
						] );

					} );

				}

			} );

			$this->container()[ Dispatcher::class ] = ApplicationEvent::dispatcher();


		}


		/**
		 * Get page hook.
		 * Slightly modified version of code from wp-admin/admin.php.
		 *
		 * @return string|null
		 */
		private function getAdminPageHook() : ?string {

			global $pagenow, $plugin_page;

			if ( $plugin_page ) {

				return get_plugin_page_hook( $plugin_page, $pagenow );

			}

			return null;

		}

	}
