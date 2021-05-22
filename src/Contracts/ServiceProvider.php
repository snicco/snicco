<?php


	declare( strict_types = 1 );


	namespace WPEmerge\Contracts;

	use Contracts\ContainerAdapter;
	use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Facade\WP;

    abstract class ServiceProvider {

		/**
		 * @var ContainerAdapter
		 */
		protected $container;

		/**
		 * @var ApplicationConfig
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

        protected function requestType () : string
        {

            if ( ! WP::isAdmin() ) {

                return IncomingWebRequest::class;

            }

            if ( WP::isAdminAjax() ) {

                return IncomingAjaxRequest::class;

            }

            return  IncomingAdminRequest::class;

        }

	}
