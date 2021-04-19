<?php


	namespace WPEmerge\Helpers;

	use Closure;
	use Contracts\ContainerAdapter;
	use WPEmerge\Application\GenericFactory;
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
		 * Constructor.
		 *
		 *
		 * @param  GenericFactory  $factory
		 */
		public function __construct( GenericFactory $factory, ContainerAdapter $container, array $namespaces ) {

			$this->factory               = $factory;
			$this->container             = $container;
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


				return $container->call( $callable, $parameters );

			} );

			return $handler;

		}

	}
