<?php


	namespace WPEmerge\Helpers;

	use Closure;
	use Contracts\ContainerAdapter;
	use Illuminate\Support\Str;
	use WPEmerge\Application\GenericFactory;

	/**
	 * Handler factory.
	 */
	class HandlerFactory {

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
		 * Constructor.
		 *
		 *
		 * @param  GenericFactory  $factory
		 */
		public function __construct( GenericFactory $factory, ContainerAdapter $container ) {

			$this->factory = $factory;
			$this->container = $container;
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

			$handler = new Handler( $this->factory, $raw_handler, $default_method, $namespace );

			$container = $this->container;

			$handler->setExecutable(function ($class, $method, $args ) use ( $container ) {

				return $container->call( $class . '@' . $method, $args );

			});

			return $handler;

		}

	}
