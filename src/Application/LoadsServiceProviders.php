<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\ServiceProviders\AliasServiceProvider;
	use WPEmerge\ServiceProviders\EventServiceProvider;
	use WPEmerge\ServiceProviders\ExceptionsServiceProvider;
	use WPEmerge\ServiceProviders\FactoryServiceProvider;
	use WPEmerge\ServiceProviders\KernelServiceProvider;
	use WPEmerge\ServiceProviders\RequestsServiceProvider;
	use WPEmerge\ServiceProviders\ResponsesServiceProvider;
	use WPEmerge\ServiceProviders\ApplicationServiceProvider;
	use WPEmerge\ServiceProviders\RoutingServiceProvider;
	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\ServiceProviders\ViewServiceProvider;

	/**
	 * Load core service providers that hold all internal services
	 * required for every request.
	 *
	 * @todo We need integration test for all Service Providers to check if we wired
	 * everything correctly.
	 *
	 */
	trait LoadsServiceProviders {

		/**
		 * Core service providers
		 *
		 * @var string[]
		 */
		private $service_providers = [
			EventServiceProvider::class,
			AliasServiceProvider::class,
			FactoryServiceProvider::class,
			ApplicationServiceProvider::class,
			KernelServiceProvider::class,
			ExceptionsServiceProvider::class,
			RequestsServiceProvider::class,
			ResponsesServiceProvider::class,
			RoutingServiceProvider::class,
			ViewServiceProvider::class,
		];


		/**
		 *
		 * Register and bootstrap all service providers.
		 *
		 */
		public function loadServiceProviders( ContainerAdapter $container ) :void {

			$user_providers = $this->config->get( 'providers', [] );

			$providers = collect( $this->service_providers )->merge( $user_providers );

			$container->instance('_providers', $providers->all());


			$providers = $providers->each( [ $this, 'isValid' ] )
			                       ->map( [ $this, 'instantiate' ] )
			                       ->each( [ $this, 'addToContainer' ] )
			                       ->toArray();

			$this->registerServiceProviders( $providers );

			$this->bootstrapServiceProviders( $providers );

		}

		/**
		 * Register all service providers.
		 *
		 * @param  ServiceProvider[]  $service_providers
		 *
		 * @return void
		 */
		private function registerServiceProviders( array $service_providers ) {

			foreach ( $service_providers as $provider ) {

				$provider->register();

			}
		}

		/**
		 * Bootstrap all service providers.
		 * At this point all services are bootstrapped in the IoC-Container
		 *
		 * @param  ServiceProvider[]  $service_providers
		 *
		 * @return void
		 */
		private function bootstrapServiceProviders( array $service_providers ) {

			foreach ( $service_providers as $provider ) {

				$provider->bootstrap();

			}
		}

		/**
		 * @param  string  $provider
		 *
		 * @throws ConfigurationException
		 */
		private function isValid( string $provider ) {

			if ( ! is_subclass_of( $provider, ServiceProvider::class ) ) {
				throw new ConfigurationException(
					'The following class does not implement ' .
					ServiceProvider::class . ': ' . $provider
				);
			}

		}

		/**
		 * @param  string  $provider
		 *
		 * @return ServiceProvider
		 */
		private function instantiate( string $provider ) : ServiceProvider {

			return new $provider($this->container(), $this->config);

		}

		/**
		 * @param  ServiceProvider  $provider
		 *
		 */
		private function addToContainer( ServiceProvider $provider ) {

			$this->container()->instance(get_class($provider), $provider);

		}

	}
