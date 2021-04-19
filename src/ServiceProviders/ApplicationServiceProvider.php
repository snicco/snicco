<?php



	namespace WPEmerge\ServiceProviders;

	use BetterWpdb\Contracts\WpdbInterface;
	use BetterWpdb\DbFactory;
	use BetterWpdb\WpConnection;
	use Contracts\ContainerAdapter;
	use Illuminate\Support\Arr;
	use WPEmerge\Application\ClosureFactory;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Contracts\RouteModelResolver;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Helpers\HandlerFactory;
	use WPEmerge\Helpers\MixedType;
	use WPEmerge\WpdbRouteModelResolver;

	use function wp_mkdir_p;
	use function wp_upload_dir;

	use const WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY;
	use const WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY;
	use const WPEMERGE_APPLICATION_KEY;
	use const WPEMERGE_CONFIG_KEY;
	use const WPEMERGE_CONTAINER_ADAPTER;
	use const WPEMERGE_HELPERS_HANDLER_FACTORY_KEY;

	/**
	 * Provide application dependencies.
	 *
	 */
	class ApplicationServiceProvider implements ServiceProviderInterface {


		use ExtendsConfigTrait;

		public function register( ContainerAdapter $container ) {

			$this->extendConfig( $container, 'providers', [] );

			$upload_dir = wp_upload_dir();

			$cache_dir = MixedType::addTrailingSlash( $upload_dir['basedir'] ) . 'wpemerge' . DIRECTORY_SEPARATOR . 'cache';

			$this->extendConfig( $container, 'cache', [
				'path' => $cache_dir,
			] );

			$container->singleton(RouteModelResolver::class, function ($container) {

				global $wpdb;

				if ( ! $wpdb instanceof WpdbInterface ) {

					return new WpdbRouteModelResolver(new WpConnection(DbFactory::make($wpdb)));

				}

				return new WpdbRouteModelResolver(new WpConnection($wpdb));

			});

			$container->bind( WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY, function ( $container ) {

				return new GenericFactory(
					$container[ WPEMERGE_CONTAINER_ADAPTER ]
				);
			} );

			$container->bind( WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY, function ( $container ) {

				return new ClosureFactory( $container[ WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY ] );
			} );

			$container->bind( WPEMERGE_HELPERS_HANDLER_FACTORY_KEY, function ( $container ) {


				return new HandlerFactory(

					$container[ WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY ],
					// $container is the concrete implementation. In the default setup the illuminate
					// container
					$container[WPEMERGE_CONTAINER_ADAPTER],

					$container[RouteModelResolver::class],

					Arr::get($container[WPEMERGE_CONFIG_KEY], 'controller_namespaces', [])

				);

			} );

			$app = $container[ WPEMERGE_APPLICATION_KEY ];
			$app->alias( 'app', WPEMERGE_APPLICATION_KEY );
			$app->alias( 'closure', WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY );

		}


		public function bootstrap( $container ) {

			$cache_dir = $container[ WPEMERGE_CONFIG_KEY ]['cache']['path'];
			wp_mkdir_p( $cache_dir );

		}

	}
