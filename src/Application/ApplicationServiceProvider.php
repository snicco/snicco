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
	use WPEmerge\Helpers\HandlerFactory;
	use WPEmerge\Helpers\MixedType;
	use WPEmerge\ServiceProviders\ExtendsConfigTrait;
	use WPEmerge\ServiceProviders\ServiceProviderInterface;

	/**
	 * Provide application dependencies.
	 *
	 * @codeCoverageIgnore
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

			$container->bind( WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY, function ( $container ) {

				return new GenericFactory(
					$container[ WPEMERGE_CONTAINER_ADAPTER ]
				);
			} );

			$container->bind( WPEMERGE_APPLICATION_CLOSURE_FACTORY_KEY, function ( $container ) {

				return new ClosureFactory( $container[ WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY ] );
			} );

			$container->bind( WPEMERGE_HELPERS_HANDLER_FACTORY_KEY, function ( $container ) {

				return new HandlerFactory( $container[ WPEMERGE_APPLICATION_GENERIC_FACTORY_KEY ] );
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
