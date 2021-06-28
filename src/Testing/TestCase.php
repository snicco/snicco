<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use Carbon\Carbon;
    use Carbon\CarbonImmutable;
    use Codeception\TestCase\WPTestCase;
    use Illuminate\Support\Str;
    use Mockery\Exception\InvalidCountException;
    use Nyholm\Psr7Server\ServerRequestCreator;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationConfig;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Http\ResponseEmitter;
    use WPEmerge\Session\Session;
    use WPEmerge\Session\SessionServiceProvider;

    abstract class TestCase extends WPTestCase
    {

        use MakesHttpRequests;
        use InteractsWithContainer;

        /**
         * @var Application
         */
        protected $app;

        /** @var Session */
        protected $session;

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
         * Return an instance of your Application. DONT BOOT THE APPLICATION.
         */
        abstract public function createApplication() : Application;

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

        protected function setUp() : void
        {

            parent::setUp();

            if ( ! $this->app) {
                $this->refreshApplication();
            }

            $this->app->boot(false);

            $this->replaceBindings();

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

            if (class_exists('Mockery')) {
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

            if (class_exists(Carbon::class)) {
                Carbon::setTestNow();
            }

            if (class_exists(CarbonImmutable::class)) {
                CarbonImmutable::setTestNow();
            }

            parent::tearDown();
        }

        protected function withAddedConfig(array $items)
        {

            foreach ($items as $key => $value) {
                $this->config->set($key, $value);
            }

            return $this;

        }

        protected function refreshApplication()
        {

            $this->app = $this->createApplication();

        }

        protected function setUpTraits()
        {

            $traits = array_flip(class_uses_recursive(static::class));


        }

        protected function callBeforeApplicationDestroyedCallbacks()
        {

            foreach ($this->before_application_destroy_callbacks as $callback) {
                $callback();

            }
        }

        protected function setProperties()
        {

            $this->config = $this->app->config();

            if ( in_array(SessionServiceProvider::class, $this->config->get('app.providers'))){

                $this->session = $this->app->resolve(Session::class);

            }

            $this->request_factory = $this->app->resolve(ServerRequestFactoryInterface::class);
            $this->kernel = $this->app->resolve(HttpKernel::class);

        }

        private function replaceBindings()
        {

            $this->swap(ResponseEmitter::class, new TestResponseEmitter());
        }

    }