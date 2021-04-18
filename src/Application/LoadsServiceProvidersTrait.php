<?php


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use WPEmerge\Controllers\ControllersServiceProvider;
	use WPEmerge\Csrf\CsrfServiceProvider;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\ServiceProviders\ExceptionsServiceProvider;
	use WPEmerge\ServiceProviders\KernelsServiceProvider;
	use WPEmerge\ServiceProviders\MiddlewareServiceProvider;
	use WPEmerge\ServiceProviders\RequestsServiceProvider;
	use WPEmerge\ServiceProviders\ResponsesServiceProvider;
	use WPEmerge\ServiceProviders\ApplicationServiceProvider;
	use WPEmerge\ServiceProviders\RoutingServiceProvider;
	use WPEmerge\ServiceProviders\FlashServiceProvider;
	use WPEmerge\ServiceProviders\OldInputServiceProvider;
	use WPEmerge\ServiceProviders\ServiceProviderInterface;
	use WPEmerge\ServiceProviders\ViewServiceProvider;

	/**
	 * Load core service providers that hold all internal services
	 * required for every request.
	 */
	trait LoadsServiceProvidersTrait {

		/**
		 * Core service providers
		 *
		 * @var string[]
		 */
		private $service_providers = [
			ApplicationServiceProvider::class,
			KernelsServiceProvider::class,
			ExceptionsServiceProvider::class,
			RequestsServiceProvider::class,
			ResponsesServiceProvider::class,
			RoutingServiceProvider::class,
			ViewServiceProvider::class,
			ControllersServiceProvider::class,
			MiddlewareServiceProvider::class,
			CsrfServiceProvider::class,
			FlashServiceProvider::class,
			OldInputServiceProvider::class,
		];


		/**
		 *
		 * Register and bootstrap all service providers.
		 *
		 *
		 * @param  ContainerAdapter  $container
		 *
		 * @return void
		 */
		private function loadServiceProviders( ContainerAdapter $container ) {

			$user_providers = collect( $container[ WPEMERGE_CONFIG_KEY ] )->get( 'providers', [] );

			$providers = collect( $this->service_providers )->merge( $user_providers );

			$container[ WPEMERGE_SERVICE_PROVIDERS_KEY ] = $providers->all();

			$providers = $providers->each( [ $this, 'isValid' ] )
			                       ->map( [ $this, 'instantiate' ] )
			                       ->each( [ $this, 'addToContainer' ] )
			                       ->toArray();

			$this->registerServiceProviders( $providers, $container );

			$this->bootstrapServiceProviders( $providers, $container );

		}

		/**
		 * Register all service providers.
		 *
		 * @param  ServiceProviderInterface[]  $service_providers
		 * @param  ContainerAdapter  $container
		 *
		 * @return void
		 */
		private function registerServiceProviders( array $service_providers, ContainerAdapter $container ) {

			foreach ( $service_providers as $provider ) {

				$provider->register( $container );

			}
		}

		/**
		 * Bootstrap all service providers.
		 * At this point all services are bootstrapped in the IoC-Container
		 *
		 * @param  ServiceProviderInterface[]  $service_providers
		 * @param  ContainerAdapter  $container
		 *
		 * @return void
		 */
		private function bootstrapServiceProviders( array $service_providers, ContainerAdapter $container ) {

			foreach ( $service_providers as $provider ) {

				$provider->bootstrap( $container );

			}
		}

		/**
		 * @param  string  $provider
		 *
		 * @throws ConfigurationException
		 */
		private function isValid( string $provider ) {

			if ( ! is_subclass_of( $provider, ServiceProviderInterface::class ) ) {
				throw new ConfigurationException(
					'The following class does not implement ' .
					ServiceProviderInterface::class . ': ' . $provider
				);
			}

		}

		/**
		 * @param  string  $provider
		 *
		 * @return ServiceProviderInterface
		 */
		private function instantiate( string $provider ) : ServiceProviderInterface {

			return new $provider();

		}

		/**
		 * @param  ServiceProviderInterface  $provider
		 *
		 */
		private function addToContainer( ServiceProviderInterface $provider ) {

			$this->container()[ get_class( $provider ) ] = $provider;


		}

	}
