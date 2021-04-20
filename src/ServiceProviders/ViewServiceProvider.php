<?php
	/**
	 * @package   WPEmerge
	 * @author    Atanas Angelov <hi@atanas.dev>
	 * @copyright 2017-2019 Atanas Angelov
	 * @license   https://www.gnu.org/licenses/gpl-2.0.html GPL-2.0
	 * @link      https://wpemerge.com/
	 */


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProviderInterface;
	use WPEmerge\Helpers\MixedType;
	use WPEmerge\View\PhpViewEngine;
	use WPEmerge\View\PhpViewFilesystemFinder;
	use WPEmerge\Contracts\ViewInterface;
	use WPEmerge\View\ViewService;

	use function get_stylesheet_directory;
	use function get_template_directory;

	use const WPEMERGE_APPLICATION_KEY;
	use const WPEMERGE_CONFIG_KEY;
	use const WPEMERGE_HELPERS_HANDLER_FACTORY_KEY;
	use const WPEMERGE_VIEW_COMPOSE_ACTION_KEY;
	use const WPEMERGE_VIEW_ENGINE_KEY;
	use const WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY;
	use const WPEMERGE_VIEW_SERVICE_KEY;

	/**
	 * Provide view dependencies
	 *
	 */
	class ViewServiceProvider implements ServiceProviderInterface {

		use ExtendsConfigTrait;

		public function register( $container ) {

			$this->extendConfig( $container, 'views', [
				get_stylesheet_directory(),
				get_template_directory(),
			] );

			$this->extendConfig( $container, 'view_composers', [
				'namespace' => 'App\\ViewComposers\\',
			] );

			$container->singleton( WPEMERGE_VIEW_SERVICE_KEY, function ( $c ) {

				return new ViewService(
					$c[ WPEMERGE_CONFIG_KEY ]['view_composers'],
					$c[ WPEMERGE_VIEW_ENGINE_KEY ],
				);
			} );

			$container->singleton( WPEMERGE_VIEW_COMPOSE_ACTION_KEY, function ( $c ) {

				return function ( ViewInterface $view ) use ( $c ) {

					$view_service = $c[ WPEMERGE_VIEW_SERVICE_KEY ];
					$view_service->compose( $view );

					return $view;
				};
			} );

			$container->singleton( WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY, function ( $c ) {

				$finder = new PhpViewFilesystemFinder( MixedType::toArray( $c[ WPEMERGE_CONFIG_KEY ]['views'] ) );

				return new PhpViewEngine( $c[ WPEMERGE_VIEW_COMPOSE_ACTION_KEY ], $finder );
			} );

			$container->singleton( WPEMERGE_VIEW_ENGINE_KEY, function ( $c ) {

				return $c[ WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY ];
			} );


			$app = $container[ WPEMERGE_APPLICATION_KEY ];
			$app->alias( 'views', WPEMERGE_VIEW_SERVICE_KEY );
			$app->alias( 'view', function () use ( $app ) {

				return call_user_func_array( [ $app->views(), 'make' ], func_get_args() );
			} );
			$app->alias( 'render', function () use ( $app ) {

				return call_user_func_array( [ $app->views(), 'render' ], func_get_args() );
			} );
			$app->alias( 'layoutContent', function () use ( $app ) {

				$engine = $app->resolve( WPEMERGE_VIEW_PHP_VIEW_ENGINE_KEY );

				echo $engine->getLayoutContent();

			} );
		}


		public function bootstrap( $container ) {
			// Nothing to bootstrap.
		}

	}
