<?php


	namespace WPEmerge\Contracts;

	use Contracts\ContainerAdapter;

	/**
	 * Interface that service providers must implement
	 */
	interface ServiceProviderInterface {

		/**
		 * Register all dependencies in the IoC container.
		 *
		 * @param  ContainerAdapter  $container
		 *
		 * @return void
		 */
		public function register( ContainerAdapter $container );

		/**
		 * Bootstrap any services if needed.
		 *
		 * @param  ContainerAdapter  $container
		 *
		 * @return void
		 */
		public function bootstrap( ContainerAdapter $container );

	}
