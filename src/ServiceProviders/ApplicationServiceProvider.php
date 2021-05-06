<?php


	namespace WPEmerge\ServiceProviders;

	use BetterWpdb\ConnectionResolver;
	use BetterWpdb\Contracts\WpdbInterface;
	use BetterWpdb\DbFactory;
	use BetterWpdb\WpConnection;
	use Contracts\ContainerAdapter;
	use Illuminate\Database\Eloquent\Model as Eloquent;
	use BetterWpHooks\Contracts\Dispatcher;
	use WPEmerge\Application\ClosureFactory;
	use WPEmerge\Application\GenericFactory;
	use WPEmerge\Contracts\RouteModelResolver;
	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Support\Path;
	use WPEmerge\Traits\ExtendsConfig;
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

		use ExtendsConfig;

		public function register( ContainerAdapter $container ) {

			$this->extendConfig( $container, 'providers', [] );

			$this->extendConfig($container, 'strict.mode', false );

			$upload_dir = wp_upload_dir();

			$cache_dir = Path::addTrailingSlash( $upload_dir['basedir'] ) . 'wpemerge' . DIRECTORY_SEPARATOR . 'cache';

			/** @todo Refactor to custom filesystem class. */
			$this->extendConfig( $container, 'cache', [
				'path' => $cache_dir,
			] );




			$container->singleton('strict.mode', function ($container) {

				return $container[WPEMERGE_CONFIG_KEY]['strict.mode'];

			});

			$app = $container[ WPEMERGE_APPLICATION_KEY ];
			$app->alias( 'app', WPEMERGE_APPLICATION_KEY );
			$app->alias( 'closure', WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY );

		}


		public function bootstrap( $container ) {

			/** @todo Refactor to custom filesystem class.  */
			// $cache_dir = $container[ WPEMERGE_CONFIG_KEY ]['cache']['path'];
			// wp_mkdir_p( $cache_dir );

		}

	}
