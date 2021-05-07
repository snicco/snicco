<?php


	namespace WPEmerge\ServiceProviders;

	use WPEmerge\Contracts\ServiceProvider;
	use WPEmerge\Support\Path;



	/**
	 * Provide application dependencies.
	 *
	 */
	class ApplicationServiceProvider extends ServiceProvider {


		public function register() :void  {


			$this->config->extend('strict_mode', false );

			$upload_dir = wp_upload_dir();

			$cache_dir = Path::addTrailingSlash( $upload_dir['basedir'] ) . 'wpemerge' . DIRECTORY_SEPARATOR . 'cache';

			/** @todo Refactor to custom filesystem class. */
			$this->config->extend('cache', ['path' => $cache_dir]);



		}


		public function bootstrap() :void  {

			/** @todo Refactor to custom filesystem class.  */
			// $cache_dir = $container[ WPEMERGE_CONFIG_KEY ]['cache']['path'];
			// wp_mkdir_p( $cache_dir );

		}

	}
