<?php


    declare(strict_types = 1);


    namespace WPEmerge\Contracts;

    use Contracts\ContainerAdapter;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Support\Arr;
    use WPEmerge\Support\Str;

    abstract class ServiceProvider
    {

        /**
         * @var ContainerAdapter
         */
        protected $container;

        /**
         * @var ApplicationConfig
         */
        protected $config;

        /** @var Application */
        protected $app;

        public function __construct(ContainerAdapter $container_adapter, ApplicationConfig $config)
        {

            $this->container = $container_adapter;
            $this->config = $config;

        }

        public function setApp ( Application $app ) {

            $this->app = $app;

        }

        /**
         * Register all dependencies in the IoC container.
         *
         * @return void
         */
        abstract public function register() : void;

        /**
         * Bootstrap any services if needed.
         *
         * @return void
         */
        abstract function bootstrap() : void;

        protected function requestType() : string
        {

            if ( ! WP::isAdmin()) {

                return IncomingWebRequest::class;

            }

            if (WP::isAdminAjax()) {

                return IncomingAjaxRequest::class;

            }

            return OutputBufferRequired::class;

        }

        /** Only use this function after all providers have been registered. */
        protected function sessionEnabled() : bool
        {

            return $this->config->get('session.enabled', false);

        }

        protected function validAppKey() : bool
        {

            $key = $this->appKey();

            if (Str::startsWith($key, $prefix = 'base64:')) {

                $key = base64_decode(Str::after($key, $prefix));

            }

            if ( mb_strlen($key, '8bit') !== 32  ) {

                return false;

            }

            return true;

        }

        protected function appKey () {

            return $this->config->get('app_key');

        }

        protected function extendRoutes($routes) {

            $new_routes = Arr::wrap($routes);

            $routes = Arr::wrap($this->config->get('routing.definitions'));

            $routes = array_merge($routes, Arr::wrap($new_routes));

            $this->config->set('routing.definitions', $routes);

        }

    }
