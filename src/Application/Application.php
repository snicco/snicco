<?php


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use Illuminate\Config\Repository;
	use SniccoAdapter\BaseContainerAdapter;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Support\VariableBag;

	class Application {


		use ManagesAliases;
		use LoadsServiceProviders;
		use HasContainer;


		private $bootstrapped = false;

		/**
		 * @var array
		 */
		private $config;


		public function __construct( ContainerAdapter $container) {

			$this->setContainerAdapter( $container );
			$this->container()[ WPEMERGE_APPLICATION_KEY ]   = $this;
			$this->container()[ WPEMERGE_CONTAINER_ADAPTER ] = $this->container();


		}


		/**
		 * Make and assign a new application instance.
		 *
		 * @param  string|ContainerAdapter  $container_adapter  ::class or default
		 *
		 * @return static
		 */
		public static function create( $container_adapter ) : Application {

			return new static(
				( $container_adapter !== 'default' ) ? $container_adapter : new BaseContainerAdapter()
			);
		}

		/**
		 * Bootstrap the application and loads all service providers.
		 *
		 * @param  array  $config  The configuration provided by a user during bootstrapping.
		 *
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		public function bootstrap( array $config = [] ) :void {


			if ( $this->bootstrapped ) {

				throw new ConfigurationException( static::class . ' already bootstrapped.' );

			}

			$this->bindConfig( $config );

			$this->loadServiceProviders( $this->container());

			$this->bootstrapped = true;


		}


		private function bindConfig( array $config ) {

			$config = new ApplicationConfig($config);

			$this->container()->instance(ApplicationConfig::class, $config );
			$this->config = $config;

		}


	}
