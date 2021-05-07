<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Support\Path;
	use WPEmerge\Traits\ExtendsConfig;



	/**
	 * Provide application dependencies.
	 *
	 */
	class ApplicationServiceProvider extends ServiceProvider {

		use ExtendsConfig;

		public function register() :void  {

			$this->extendConfig( $this->container, 'providers', [] );

			$this->extendConfig($this->container, 'strict.mode', false );

			$upload_dir = wp_upload_dir();

			$cache_dir = Path::addTrailingSlash( $upload_dir['basedir'] ) . 'wpemerge' . DIRECTORY_SEPARATOR . 'cache';

			/** @todo Refactor to custom filesystem class. */
			$this->extendConfig( $this->container, 'cache', [
				'path' => $cache_dir,
			] );




			$this->container->singleton('strict.mode', function () {

				return $this->config['strict.mode'];

			});


		}


		public function bootstrap() :void  {

			/** @todo Refactor to custom filesystem class.  */
			// $cache_dir = $container[ WPEMERGE_CONFIG_KEY ]['cache']['path'];
			// wp_mkdir_p( $cache_dir );

		}

	}
