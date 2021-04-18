<?php



	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use WPEmerge\Exceptions\ClassNotFoundException;


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
		 * Resolve a class instance from the IoC-Container Adapter.
		 *
		 * @param  string  $class
		 *
		 * @return object
		 * @throws ClassNotFoundException
		 *
		 *
		 */
		public function make( string $class )  {


			try {

				return $this->container->make( $class );

			}

			catch ( \Exception $e ) {

				throw new ClassNotFoundException(
					'Class not found: ' . $class .'.' . PHP_EOL .
					'It was not possible to resolve the class ' . $class . ' from the service container.' . PHP_EOL .
					$e->getMessage()
				);

			}



		}




	}
