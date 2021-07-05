<?php


    declare(strict_types = 1);


    namespace BetterWP\Testing;

    use Carbon\Carbon;
    use Carbon\CarbonImmutable;
    use Codeception\TestCase\WPTestCase;
    use Illuminate\Support\Str;
    use Mockery;
    use Mockery\Exception\InvalidCountException;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Tests\helpers\TravelsTime;
    use Tests\stubs\TestApp;
    use Throwable;
    use BetterWP\Application\Application;
    use BetterWP\Application\Config;
    use BetterWP\Events\Event;
    use BetterWP\Contracts\AbstractRouteCollection;
    use BetterWP\Contracts\ErrorHandlerInterface;
    use BetterWP\Contracts\Middleware;
    use BetterWP\Contracts\RouteRegistrarInterface;
    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\ExceptionHandling\NullErrorHandler;
    use BetterWP\Support\WP;
    use BetterWP\Http\Delegate;
    use BetterWP\Http\HttpKernel;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\Psr7\Response;
    use BetterWP\Http\ResponseEmitter;
    use BetterWP\Routing\Route;
    use BetterWP\Routing\Router;
    use BetterWP\Routing\RouteRegistrar;
    use BetterWP\Session\Session;
    use BetterWP\Session\SessionServiceProvider;
    use BetterWP\Support\Arr;
    use BetterWP\Testing\Concerns\InteractsWithAuthentication;
    use BetterWP\Testing\Concerns\InteractsWithContainer;
    use BetterWP\Testing\Concerns\InteractsWithMail;
    use BetterWP\Testing\Concerns\InteractsWithSession;
    use BetterWP\Testing\Concerns\InteractsWithWordpressUsers;
    use BetterWP\Testing\Concerns\MakesHttpRequests;

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

        /** @var Config */
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

            if ( ! $this->app) {

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

            if ( ! $this->defer_boot) {
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

            Event::setInstance(null);
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

        protected function withReplacedConfig($items, $value) : TestCase
        {

            $items = is_array($items) ? $items : [$items => $value];

            foreach ($items as $key => $value) {

                $this->config->remove($key);

            }

            return $this->withAddedConfig($items);

        }

        protected function withAddedMiddleware(string $group, $middleware) : TestCase
        {

            $this->config->extend("middleware.groups.$group", Arr::wrap($middleware));

            return $this;
        }

        /**
         * Disable all or some middleware for the test.
         *
         * @param  string|array|null  $middleware
         *
         * @return $this
         */
        protected function withoutMiddleware($middleware = null) : TestCase
        {

            if (is_null($middleware)) {

                $this->app->config()->set('middleware.disabled', true);

                return $this;

            }

            foreach ((array) $middleware as $abstract) {

                if ( ! class_exists($abstract)) {
                    $aliases = $this->config->get('middleware.aliases');
                    $m = $aliases[$abstract] ?? null;
                    if ($m === null) {
                        throw new \RuntimeException("You are trying to disable middleware [$abstract] which does not seem to exist.");
                    }
                    $abstract = $m;
                }

                $this->app->container()->singleton($abstract, function () {

                    return new class extends Middleware {

                        public function handle(Request $request, Delegate $next) : ResponseInterface
                        {

                            return $next($request);
                        }

                    };
                });

            }

            return $this;
        }

        /**
         *
         * Disables exception handling. Exceptions will not be converted to HTTP Responses
         *
         * @return TestCase
         */
        protected function withoutExceptionHandling() : TestCase
        {

            $this->config->set('app.exception_handling', false);
            $this->instance(ErrorHandlerInterface::class, new NullErrorHandler());

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

        protected function loadRoutes() : TestCase
        {

            if ( $this->routes_loaded ) {
                return $this;
            }

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

            return $this;

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

        }

        private function refreshApplication()
        {

            $this->app = $this->createApplication();
        }

    }