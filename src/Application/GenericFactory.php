<?php
	/**
	 * @package   WPEmerge
	 * @author    Atanas Angelov <hi@atanas.dev>
	 * @copyright 2017-2019 Atanas Angelov
	 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
	 * @link      https://wpemerge.com/
	 */

	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use WPEmerge\Exceptions\ClassNotFoundException;
	use WPEmerge\Application\ContainerAdapterInterface as ContainerInterface;


	/**
	 * Generic class instance factory.
	 */
	class GenericFactory {

		/**
		 * Container.
		 *
		 * @var ContainerAdapter
		 */
		protected $container = NULL;

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
		 * @throws ClassNotFoundException
		 * @todo Make Construction of Handlers and Middleware use the AdapterInterface. This is the right place.
		 *
		 *
		 */
		public function make( $class ) {


			if ( $resolved_class = $this->tryFromController( $class ) ) {

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
		 * @throws \WPEmerge\Exceptions\ClassNotFoundException
		 */
		private function tryToInstantiate( $class ) {


			if ( ! class_exists( $class ) ) {
				throw new ClassNotFoundException( 'Class not found: ' . $class );
			}

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
		private function tryFromController( $class ) {

			return $this->container->make( $class );

		}



	}
