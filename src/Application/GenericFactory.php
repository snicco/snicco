<?php



	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use WPEmerge\Exceptions\ClassNotFoundException;

	/**
	 * Generic class instance factory.
	 */
	class GenericFactory {

		/**
		 * Container.
		 *
		 * @var ContainerAdapter
		 */
		protected $container = null;

		/**
		 * Constructor.
		 *
		 * @codeCoverageIgnore
		 *
		 * @param  ContainerAdapter  $container
		 */
		public function __construct( ContainerAdapter $container ) {

			$this->container = $container;
		}

		/**
		 * Make a class instance.
		 *
		 * @param  string  $class
		 *
		 * @return object
		 * @throws ClassNotFoundException|\ReflectionException
		 *
		 *
		 */
		public function make( string $class )  {


			if ( ! class_exists( $class ) ) {
				throw new ClassNotFoundException( 'Class not found: ' . $class );
			}

			if ( $resolved_class = $this->tryFromContainer( $class ) ) {

				return $resolved_class;

			}

			return $this->tryToInstantiate( $class );


		}

		/**
		 *
		 * Lets see if we can instantiate the class
		 *
		 *
		 * @param $class
		 *
		 * @return mixed
		 * @throws \ReflectionException
		 */
		private function tryToInstantiate( $class ) {


			$constructor = ( new \ReflectionClass( $class ) )->getConstructor();

			if ( $constructor && $count = count( $constructor->getParameters() ) > 0 ) {

				throw new \ArgumentCountError( 'The class: "' . $class . '" has
				constructor arguments. Could not auto resolve the class because its not in the Service Container.' );
			}

			return new $class();

		}

		/**
		 *
		 * Try to resolve the class from the applications
		 * container adapter.
		 *
		 * @param $class
		 *
		 * @return null|object
		 */
		private function tryFromContainer( $class ) : ?object {


			return $this->container->make( $class );


		}


	}
