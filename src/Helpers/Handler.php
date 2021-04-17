<?php
	/**
	 * @package   WPEmerge
	 * @author    Atanas Angelov <hi@atanas.dev>
	 * @copyright 2017-2019 Atanas Angelov
	 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
	 * @link      https://wpemerge.com/
	 */


	namespace WPEmerge\Helpers;

	use Closure;
	use Illuminate\Support\Str;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Exceptions\ClassNotFoundException;
	use WPEmerge\Exceptions\ConfigurationException;

	/**
	 * Represent a generic handler - a Closure or a class method to be resolved from the service
	 * container
	 */
	class Handler {

		/**
		 * Injection Factory.
		 *
		 * @var GenericFactory
		 */
		protected $factory = null;

		/**
		 * Parsed handler
		 *
		 * @var array|Closure
		 */
		protected $handler = null;

		/**
		 * Constructor
		 *
		 * @param  GenericFactory  $factory
		 * @param  string|Closure  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 */
		public function __construct( GenericFactory $factory, $raw_handler, $default_method = '', $namespace = '' ) {

			$this->factory = $factory;

			$handler = $this->parse( $raw_handler, $default_method, $namespace );

			if ( $handler === null ) {
				throw new ConfigurationException( 'No or invalid handler provided.' );
			}

			$this->handler = $handler;
		}

		/**
		 * Parse a raw handler to a Closure or a [class, method] array
		 *
		 * @param  string|Closure  $raw_handler
		 * @param  string  $default_method
		 * @param  string  $namespace
		 *
		 * @return array|Closure|null
		 */
		protected function parse( $raw_handler, $default_method, $namespace ) {

			if ( $raw_handler instanceof Closure ) {
				return $raw_handler;
			}

			return $this->parseFromString( $raw_handler, $default_method, $namespace );

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
		protected function parseFromString( $raw_handler, $default_method, $namespace ) {

			[ $class, $method ] = Str::parseCallback($raw_handler, $default_method);

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
		 * @todo Update the forked wpemperge repo. Test Exceptions
		 */
		public function make() {

			$handler = $this->get();

			if ( $handler instanceof Closure ) {
				return $handler;
			}

			$namespace = $handler['namespace'];
			$class     = $handler['class'];

			try {
				$full_class_path = $this->findClass( $class, $namespace );
			}
			catch ( ClassNotFoundException $e ) {
				throw new ClassNotFoundException( 'Class not found - tried to find: ' . $class . ', in three namespaces in your wpemerge config' );
			}

			return $this->factory->make( $full_class_path );
		}

		/**
		 * Execute the parsed handler with any provided arguments and return the result.
		 *
		 * @param  mixed ,...$arguments
		 *
		 * @return mixed
		 */
		public function execute() {

			$arguments = func_get_args();
			$instance  = $this->make();

			if ( $instance instanceof Closure ) {
				// Remove the container_adapter closure.
				array_pop( $arguments );

				// Dont force to accept and empty view if not on web route.
				return call_user_func_array( $instance, $this->trimArguments( $arguments ) );
			}

			return $this->callInstanceMethod( $instance, $this->get()['method'], $arguments );
		}

		/**
		 *
		 * Use the containerAdapter to call the Handler Method
		 *
		 * @param $instance
		 * @param $method
		 * @param $arguments
		 *
		 * @return false|mixed
		 *
		 * @todo inject the config into the handler.
		 */
		private function callInstanceMethod( $instance, $method, $arguments ) {

			$callable = array_pop( $arguments );

			return $callable( [ $instance, $method, ], $this->trimArguments( $arguments ) );
		}


		private function findClass( $class, $path ) {


			if ( class_exists( $class_no_namespace = $class ) ) {
				return $class_no_namespace;
			}

			if ( class_exists( $class_with_namespace = $path . $class ) ) {
				return $class_with_namespace;
			}

			$config     = App::resolve( WPEMERGE_CONFIG_KEY );
			$namespaces = array_column( array_column( $config['routes'], 'attributes' ), 'namespace' );

			foreach ( $namespaces as $namespace ) {
				if ( class_exists( $namespace . $class ) ) {
					return $namespace . $class;
				}

				throw new ClassNotFoundException();
			}
		}


		private function trimArguments( $arguments ) {

			return $arguments = array_filter( $arguments, function ( $value ) {

				return $value != '' && $value != [];
			} );
		}

	}
