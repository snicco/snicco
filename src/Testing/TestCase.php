<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use Carbon\Carbon;
    use Carbon\CarbonImmutable;
    use Codeception\TestCase\WPTestCase;
    use Illuminate\Support\Str;
    use Mockery;
    use Mockery\Exception\InvalidCountException;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Tests\helpers\TravelsTime;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\RouteRegistrar;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Support\Arr;
    use WPEmerge\Testing\Concerns\InteractsWithAuthentication;
    use WPEmerge\Testing\Concerns\InteractsWithContainer;
    use WPEmerge\Testing\Concerns\InteractsWithMail;
    use WPEmerge\Testing\Concerns\InteractsWithSession;
    use WPEmerge\Testing\Concerns\InteractsWithWordpressUsers;
    use WPEmerge\Testing\Concerns\MakesHttpRequests;

    abstract class TestCase extends WPTestCase
    {

        use MakesHttpRequests;
        use InteractsWithContainer;
        use InteractsWithSession;
        use InteractsWithAuthentication;
        use InteractsWithWordpressUsers;
        use InteractsWithMail;
        use TravelsTime;

        /**
         * @var Application
         */
        protected $app;

        /** @var Session */
        protected $session;

        /** @var Request */
        protected $request;

        /** @var ApplicationConfig */
        protected $config;

        /** @var ServerRequestFactoryInterface */
        protected $request_factory;

        /**
         * @var HttpKernel
         */
        protected $kernel;

        /**
         * @var callable[]
         */
        private $after_application_created_callbacks = [];

        /**
         * @var callable[]
         */
        private $before_application_destroy_callbacks = [];

        /**
         * @var bool
         */
        private $set_up_has_run;

        /**
         * @var Route[]
         */
        private $additional_routes = [];

        /**
         * @var bool
         */
        protected $defer_boot = false;

        /** @var bool */
        protected $routes_loaded = false;

        /**
         * @var callable[]
         */
        protected $after_config_loaded_callbacks = [];

        /**
         * Return an instance of your Application. DONT BOOT THE APPLICATION.
         */
        abstract public function createApplication() : Application;

        /**
         * @return ServiceProvider[]
         */
        public function packageProviders() : array
        {

            return [];
        }

        protected function afterApplicationCreated(callable $callback)
        {

            $this->after_application_created_callbacks[] = $callback;

            if ($this->set_up_has_run) {
                $callback();
            }
        }

        protected function beforeApplicationDestroyed(callable $callback)
        {

            $this->before_application_destroy_callbacks[] = $callback;
        }

        protected function afterLoadingConfig(callable $callback)
        {

            $this->after_config_loaded_callbacks[] = $callback;
        }

        protected function setUp() : void
        {

            parent::setUp();

            $this->backToPresent();

            if ( ! $this->app ) {

                $this->refreshApplication();

            }

            $this->app->boot(false);

            $this->config = $this->app->config();

            foreach ($this->after_config_loaded_callbacks as $callback) {
                $callback();
            }

            $this->config->extend('app.providers', $this->packageProviders());
            $this->request_factory = $this->app->resolve(ServerRequestFactoryInterface::class);
            $this->replaceBindings();

            if ( ! $this->defer_boot ) {
                $this->boot();
            }

        }

        protected function boot()
        {

            if ($this->set_up_has_run) {
                $this->fail('TestCase booted twice');
            }

            $this->app->runningUnitTest();

            $this->bindRequest();

            $this->app->loadServiceProviders();

            $this->setUpTraits();

            $this->setProperties();

            foreach ($this->after_application_created_callbacks as $callback) {
                $callback();
            }

            $this->set_up_has_run = true;

        }

        protected function tearDown() : void
        {

            if ($this->app) {
                $this->callBeforeApplicationDestroyedCallbacks();

                $this->app = null;
            }

            $this->set_up_has_run = false;

            if (class_exists(\Mockery::class)) {

                if ($container = Mockery::getContainer()) {
                    $this->addToAssertionCount($container->mockery_getExpectationCount());
                }

                try {
                    Mockery::close();
                }
                catch (InvalidCountException $e) {
                    if ( ! Str::contains($e->getMethodName(), ['doWrite', 'askQuestion'])) {
                        throw $e;
                    }
                }
            }

            $this->backToPresent();

            ApplicationEvent::setInstance(null);
            WP::reset();

            parent::tearDown();
        }

        protected function withAddedConfig($items, $value = null) : TestCase
        {

            $items = is_array($items) ? $items : [$items => $value];

            foreach ($items as $key => $value) {

                if (is_array($this->config->get($key))) {

                    $this->config->extend($key, $value);
                }
                else {

                    $this->config->set($key, $value);

                }

            }

            return $this;

        }

        protected function withAddedMiddleware(string $group, $middleware) : TestCase
        {

            $this->config->extend("middleware.groups.$group", Arr::wrap($middleware));

            return $this;
        }

        protected function withOutConfig($keys) : TestCase
        {

            foreach (Arr::wrap($keys) as $key) {
                $this->config->remove($key);
            }

            return $this;

        }

        protected function addRoute(Route $route)
        {

            $this->additional_routes[] = $route;
        }

        protected function loadRoutes()
        {

            /** @var AbstractRouteCollection $routes */
            $routes = $this->app->resolve(AbstractRouteCollection::class);

            /** @var RouteRegistrar $registrar */
            $registrar = $this->app->resolve(RouteRegistrarInterface::class);
            $registrar->loadApiRoutes($this->config);
            $registrar->loadStandardRoutes($this->config);

            foreach ($this->additional_routes as $route) {

                $routes->add($route);

            }

            $registrar->loadIntoRouter();

            $this->routes_loaded = true;

        }

        protected function withRequest(Request $request) : TestCase
        {
            $this->request = $request;
            return $this;
        }

        private function bindRequest()
        {

            if ($this->request) {

                $this->request = $this->addCookies($this->request);
                $this->request = $this->addHeaders($this->request);
                $this->instance(Request::class, $this->request);
                return;
            }

            $request = $this->request_factory->createServerRequest('GET', $this->createUri('/test-url'), $this->default_server_variables);
            $request = $this->addCookies($request);
            $request = $this->addHeaders($request);
            $this->instance(Request::class, new Request($request));
            $this->request = $request;
        }

        private function setUpTraits()
        {

            $traits = array_flip(class_uses_recursive(static::class));


        }

        private function callBeforeApplicationDestroyedCallbacks()
        {

            foreach ($this->before_application_destroy_callbacks as $callback) {
                $callback();

            }
        }

        private function setProperties()
        {

            if (in_array(SessionServiceProvider::class, $this->config->get('app.providers')) && $this->config->get('session.enabled')) {

                $this->session = $this->app->resolve(Session::class);

            }

            $this->kernel = $this->app->resolve(HttpKernel::class);

        }

        private function replaceBindings()
        {

            $this->swap(ResponseEmitter::class, new TestResponseEmitter());
        }

        private function refreshApplication()
        {
            $this->app = $this->createApplication();
        }
    }