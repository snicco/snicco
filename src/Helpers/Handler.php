<?php


	namespace WPEmerge\Helpers;

	use Closure;
	use Illuminate\Support\Str;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Contracts\HasControllerMiddlewareInterface;
	use WPEmerge\Exceptions\ClassNotFoundException;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\Arr;
	use WPEmerge\Traits\ReflectsCallable;

	/**
	 * Represent a generic handler - a Closure or a class method to be resolved from the service
	 * container
	 */
	class Handler {

		use ReflectsCallable;

		/**
		 * Injection Factory.
		 *
		 * @var GenericFactory
		 */
		private $factory;

		/**
		 * Parsed handler
		 *
		 * @var array|Closure
		 */
		private $handler;

		/**
		 *
		 * An array of namespaces where we can
		 * search for a handler.
		 *
		 * @var array
		 */
		private $namespaces;

		/**
		 * A closure that contains the
		 * call method of the applications
		 * container adapter.
		 *
		 * @var Closure;
		 */
		private $executable = null;

		/**
		 *
		 * Stores a resolved controller instance.
		 *
		 * @var \WPEmerge\Contracts\HasControllerMiddlewareInterface
		 */
		private $resolved_instance;

		/**
		 * Constructor
		 *
		 * @param  GenericFactory  $factory
		 * @param  string|Closure|array  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 * @param  array  $namespaces
		 *
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function __construct( GenericFactory $factory, $raw_handler, $default_method = '', $namespace = '', $namespaces = [] ) {

			$this->factory = $factory;

			$this->namespaces = $namespaces;

			$handler = $this->parse( $raw_handler, $default_method, $namespace );

			if ( $handler === null ) {

				throw new ConfigurationException( 'No or invalid handler provided.' );

			}

			$this->handler = $handler;

		}


		/**
		 * Get the parsed handler
		 *
		 * @return array|Closure
		 */
		public function get() {

			return $this->handler;

		}


		/**
		 * Make an instance of the handler.
		 *
		 * @return object
		 * @throws ClassNotFoundException|\ReflectionException
		 */
		public function make() {

			$handler = $this->get();

			if ( $handler instanceof Closure ) {
				return $handler;
			}

			$namespace = $handler['namespace'];
			$class     = $handler['class'];

			try {

				$full_class_path = $this->_findClass( $class, $namespace );

			}
			catch ( ClassNotFoundException $e ) {

				throw new ClassNotFoundException( 'Class not found - tried to find: ' . $class . ', in three namespaces in of your config' );
			}

			return $this->factory->make( $full_class_path );

		}


		private function _findClass( $class, $path ) {


			if ( class_exists( $class_no_namespace = $class ) ) {
				return $class_no_namespace;
			}

			if ( class_exists( $class_with_namespace = $path . $class ) ) {
				return $class_with_namespace;
			}

			foreach ( $this->namespaces as $namespace ) {
				if ( class_exists( $namespace . $class ) ) {
					return $namespace . $class;
				}

				throw new ClassNotFoundException();
			}

			throw new ClassNotFoundException();

		}


		public function setExecutable( Closure $closure ) {

			$this->executable = $closure;

		}


		/**
		 * Execute the parsed handler with any provided arguments and return the result.
		 *
		 * @return mixed
		 * @throws \ReflectionException
		 */
		public function execute() {

			$arguments = func_get_args();

			$handler = $this->get();

			if ( $handler instanceof Closure ) {

				return call_user_func_array( $handler, $arguments );

			}

			$executable = $this->executable;

			$arguments = $this->buildNamedParameters( $callable = $this->classCallable(), $arguments );

			return $executable( $this->resolved_instance ?? $callable, $arguments );

		}


		/**
		 * Parse a callable to a Closure or a [class, method] array
		 *
		 * @param  string|Closure  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 *
		 * @return array|Closure|null
		 */
		private function parse( $raw_handler, string $default_method, string $namespace ) {

			if ( $raw_handler instanceof Closure ) {
				return $raw_handler;
			}

			if ( is_array( $raw_handler ) ) {
				return $this->parseFromArray( $raw_handler, $default_method, $namespace );
			}

			return $this->parseFromString( $raw_handler, $default_method, $namespace );

		}


		/**
		 * Parse a [Class::class, 'method'] array handler to a [class, method, namespace] array
		 *
		 * @param  array  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 *
		 * @return array|null
		 */
		private function parseFromArray( array $raw_handler, string $default_method, string $namespace ) : ?array {

			$class  = Arr::get( $raw_handler, 0, '' );
			$class  = preg_replace( '/^\\\\+/', '', $class );
			$method = Arr::get( $raw_handler, 1, $default_method );

			if ( empty( $class ) ) {
				return null;
			}

			if ( empty( $method ) ) {
				return null;
			}

			return [
				'class'     => $class,
				'method'    => $method,
				'namespace' => $namespace,
			];
		}


		/**
		 * Parse a raw string handler to a [class, method] array
		 *
		 * @param  string  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 *
		 * @return array|null
		 */
		private function parseFromString( $raw_handler, $default_method, $namespace ) {

			$raw_handler = Str::replaceFirst( '::', '@', $raw_handler );

			[ $class, $method ] = Str::parseCallback( $raw_handler, $default_method );

			if ( empty( $method ) ) {
				$method = $default_method;
			}

			if ( ! empty( $class ) && ! empty( $method ) ) {
				return [
					'class'     => $class,
					'method'    => $method,
					'namespace' => $namespace,
				];
			}

			return null;
		}

		public function controllerMiddleware() : array {

			$handler = $this->get();

			if ( $handler instanceof Closure ) {

				return [];

			}

			if ( ! $this->usesControllerMiddleware( $class = $this->buildFullNamespace( $handler['class'] ) ) ) {

				return [];

			}

			/** @var HasControllerMiddlewareInterface $instance */
			$instance = $this->factory->make( $class );

			$this->resolved_instance = $instance;

			return $instance->getMiddleware( $handler['method'] );


		}

		private function trimArguments( $arguments ) : array {

			return $arguments = array_filter( $arguments, function ( $value ) {

				return $value != '' && $value != [];
			} );
		}

		private function classCallable() : string {

			return $this->buildFullNamespace( $this->handler['class'] ) . '@' . $this->handler['method'];

		}

		private function buildFullNamespace( $class ) {

			if ( class_exists( $class_no_namespace = $class ) ) {

				return $class_no_namespace;

			}

			foreach ( $this->namespaces as $namespace ) {

				if ( class_exists( $namespace . '\\' . $class ) ) {

					return $namespace . '\\' . $class;
				}


			}

			throw new ClassNotFoundException( 'Class: ' . $class . ' not found' );

		}

		private function usesControllerMiddleware( $fully_qualified_name_space_class ) : bool {

			return $this->classImplements(
				$fully_qualified_name_space_class,
				HasControllerMiddlewareInterface::class
			);

		}


	}
