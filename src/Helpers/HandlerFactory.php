<?php


	namespace WPEmerge\Helpers;

	use Closure;
	use Contracts\ContainerAdapter;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Contracts\RouteModelResolver;
	use WPEmerge\Traits\ResolvesRouteModels;

	/**
	 * Handler factory.
	 */
	class HandlerFactory {


		use ResolvesRouteModels;

		/**
		 * Injection Factory.
		 *
		 * @var GenericFactory
		 */
		protected $factory = null;

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		private $container;

		/**
		 * @var array
		 */
		private $controller_namespaces;

		/**
		 *
		 * @var \WPEmerge\Contracts\RouteModelResolver
		 */
		private $model_resolver;

		/**
		 * Constructor.
		 *
		 *
		 * @param  GenericFactory  $factory
		 */
		public function __construct( GenericFactory $factory, ContainerAdapter $container, RouteModelResolver $model_resolver, array $namespaces ) {

			$this->factory               = $factory;
			$this->container             = $container;
			$this->model_resolver        = $model_resolver;
			$this->controller_namespaces = $namespaces;

		}

		/**
		 * Make a Handler.
		 *
		 *
		 * @param  string|Closure|array  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 *
		 * @return Handler
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function make( $raw_handler, $default_method = '', $namespace = '' ) : Handler {

			$handler = new Handler(

				$this->factory,
				$raw_handler,
				$default_method,
				$namespace,
				$this->controller_namespaces

			);

			$container = $this->container;

			$handler->setExecutable( function ( $callable, $parameters ) use ( $container ) {

				$parameters = $this->bindRouteModels($callable, $parameters);

				return $container->call( $callable, $parameters );

			} );

			return $handler;

		}

	}
