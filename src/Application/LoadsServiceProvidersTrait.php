<?php


	namespace WPEmerge\Application;

	use Contracts\ContainerAdapter;
	use WPEmerge\Controllers\ControllersServiceProvider;
	use WPEmerge\Csrf\CsrfServiceProvider;
	use WPEmerge\Exceptions\ConfigurationException;
	use WPEmerge\Exceptions\ExceptionsServiceProvider;
	use WPEmerge\Flash\FlashServiceProvider;
	use WPEmerge\Input\OldInputServiceProvider;
	use WPEmerge\Kernels\KernelsServiceProvider;
	use WPEmerge\Middleware\MiddlewareServiceProvider;
	use WPEmerge\Requests\RequestsServiceProvider;
	use WPEmerge\Responses\ResponsesServiceProvider;
	use WPEmerge\Routing\RoutingServiceProvider;
	use WPEmerge\ServiceProviders\ServiceProviderInterface;
	use WPEmerge\Support\Arr;
	use WPEmerge\View\ViewServiceProvider;

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
		 * @throws \WPEmerge\Exceptions\ConfigurationException
		 */
		private function _loadServiceProviders( ContainerAdapter $container ) {

			$config = Arr::get( $container[ WPEMERGE_CONFIG_KEY ], 'providers', [] );

			$container[ WPEMERGE_SERVICE_PROVIDERS_KEY ] = array_merge(
				$this->service_providers,
				$config
			);

			$service_providers = array_map( function ( $service_provider ) use ( $container ) {

				if ( ! is_subclass_of( $service_provider, ServiceProviderInterface::class ) ) {
					throw new ConfigurationException(
						'The following class does not implement ' .
						ServiceProviderInterface::class . ': ' . $service_provider
					);
				}

				$container[ $service_provider ] = new $service_provider();

				return $container[ $service_provider ];

			}, $container[ WPEMERGE_SERVICE_PROVIDERS_KEY ] );

			$this->registerServiceProviders( $service_providers, $container );

			$this->bootstrapServiceProviders( $service_providers, $container );
		}

		private function loadServiceProviders( ContainerAdapter $container ) {

			$user_providers = collect( $container[ WPEMERGE_CONFIG_KEY ] )->get( 'providers', [] );

			$providers = collect($this->service_providers)->merge($user_providers);

			$container[ WPEMERGE_SERVICE_PROVIDERS_KEY ] = $providers->all();

			$providers = $providers->map( [ $this, 'isValid' ] )
			                       ->map(function ($provider) use ($container) {

				$instance = new $provider();

				$container[ $provider ] = $instance;

				return $instance;

			})->all();

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

		private function isValid ( $provider ) : ServiceProviderInterface {

			if ( ! is_subclass_of( $provider, ServiceProviderInterface::class ) ) {
				throw new ConfigurationException(
					'The following class does not implement ' .
					ServiceProviderInterface::class . ': ' . $provider
				);
			}

			return $provider;

		}

	}
