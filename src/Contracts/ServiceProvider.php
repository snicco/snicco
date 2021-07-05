<?php


    declare(strict_types = 1);


    namespace WPMvc\Contracts;

    use Contracts\ContainerAdapter;
    use WPMvc\Application\Application;
    use WPMvc\Application\ApplicationConfig;
    use WPMvc\Events\IncomingAdminRequest;
    use WPMvc\Events\IncomingAjaxRequest;
    use WPMvc\Events\IncomingWebRequest;
    use WPMvc\Support\WP;
    use WPMvc\Http\Psr7\Request;
    use WPMvc\Http\ResponseFactory;
    use WPMvc\Session\SessionServiceProvider;
    use WPMvc\Support\Arr;
    use WPMvc\Support\Str;

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

        /** @var ResponseFactory */
        protected $response_factory;

        /** @var Request */
        protected $current_request;

        public function __construct(ContainerAdapter $container_adapter, ApplicationConfig $config)
        {

            $this->container = $container_adapter;
            $this->config = $config;

        }

        public function setApp(Application $app)
        {

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

        /** Only use this function after all providers have been registered. */
        protected function requestEndpoint() : string
        {
            return $this->config->get('_request_endpoint', '');
        }

        /** Only use this function after all providers have been registered. */
        protected function sessionEnabled() : bool
        {

            return $this->config->get('session.enabled', false)
                && in_array(SessionServiceProvider::class, $this->config->get('app.providers', [] ));

        }

        protected function validAppKey() : bool
        {

            $key = $this->appKey();

            if (Str::startsWith($key, $prefix = 'base64:')) {

                $key = base64_decode(Str::after($key, $prefix));

            }

            if (mb_strlen($key, '8bit') !== 32) {

                return false;

            }

            return true;

        }

        protected function appKey()
        {

            return $this->config->get('app.key', '');

        }

        protected function extendRoutes($routes)
        {

            $new_routes = Arr::wrap($routes);

            $routes = Arr::wrap($this->config->get('routing.definitions'));

            // new routes have to be added after the user provided routes to allow users to overwrite inbuilt routes.
            $routes = array_merge($routes, Arr::wrap($new_routes));

            $this->config->set('routing.definitions', $routes);

        }

        protected function extendViews($views)
        {

            $views = Arr::wrap($views);

            $old_views = $this->config->get('view.paths', []);
            $views = array_merge($old_views, $views);
            $this->config->set('view.paths', $views);

        }

        protected function withSlashes() : bool
        {

            $slashes = $this->config->get('routing.trailing_slash');

            if ($slashes === null) {

                $slashes = WP::usesTrailingSlashes();

            }

            return $slashes;

        }

        protected function responseFactory()
        {

            if ( ! $this->response_factory instanceof ResponseFactory) {

                $factory = $this->container->make(ResponseFactory::class);
                $this->container->instance(ResponseFactory::class, $factory);
                $this->response_factory = $factory;

                return $factory;

            }

            return $this->response_factory;

        }

        protected function siteUrl()
        {
            return $this->config->get('app.url');
        }

        protected function currentRequest() {

            if ( ! $this->current_request instanceof Request ) {

                $this->current_request = $this->container->make(Request::class);

            }

            return $this->current_request;

        }

    }
