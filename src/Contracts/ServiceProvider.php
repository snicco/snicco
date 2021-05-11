<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use Contracts\ContainerAdapter;
	use WPEmerge\Application\ApplicationConfig;

	abstract class ServiceProvider {

		/**
		 * @var \Contracts\ContainerAdapter
		 */
		protected $container;

		/**
		 * @var \WPEmerge\Application\ApplicationConfig
		 */
		protected $config;

		public function __construct(ContainerAdapter $container_adapter, ApplicationConfig $config) {

			$this->container = $container_adapter;
			$this->config = $config;

		}

		/**
		 * Register all dependencies in the IoC container.
		 *
		 * @return void
		 */
		abstract public function register() :void;

		/**
		 * Bootstrap any services if needed.
		 *
		 * @return void
		 */
		abstract function bootstrap() :void;

	}
