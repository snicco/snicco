<?php


    declare(strict_types = 1);


    namespace Tests\unit\Application;

    use Contracts\ContainerAdapter;
    use Mockery as m;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use Psr\Http\Message\UploadedFileFactoryInterface;
    use Psr\Http\Message\UriFactoryInterface;
    use Tests\UnitTest;
    use Tests\stubs\TestContainer;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use BetterWP\Application\Application;
    use BetterWP\Application\Config;
    use BetterWP\Contracts\ServiceProvider;
    use BetterWP\ExceptionHandling\Exceptions\ConfigurationException;
    use BetterWP\Support\WP;
    use BetterWP\Http\Psr7\Request;
    use BetterWP\Http\ResponseFactory;
    use BetterWP\Session\Encryptor;

    class ApplicationTest extends UnitTest
    {

        use CreateDefaultWpApiMocks;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /**
         * @var string
         */
        private $base_path;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->base_path = __DIR__;
            WP::setFacadeContainer($this->container);
            $this->setUpWp(VENDOR_DIR);


        }

        protected function beforeTearDown()
        {

            WP::reset();
            m::close();

        }

        /** @test */
        public function the_static_constructor_returns_an_application_instance()
        {

            $base_container = $this->createContainer();

            $application = Application::create($this->base_path, $base_container);
            $application->runningUnitTest();

            $this->assertInstanceOf(Application::class, $application);

            $this->assertSame($base_container, $application->container());

        }

        /** @test */
        public function the_application_cant_be_bootstrapped_twice()
        {

            $app = $this->newApplication();
            $app->runningUnitTest();

            try {

                $app->boot();

            }

            catch (\Throwable $e) {

                $this->fail('Application could not be bootstrapped.'.PHP_EOL.$e->getMessage());

            }

            try {

                $app->boot();

                $this->fail('Application was bootstrapped two times.');

            }

            catch (ConfigurationException $e) {

                $this->assertStringContainsString('already bootstrapped', $e->getMessage());

            }


        }

        /** @test */
        public function user_provided_config_gets_bound_into_the_di_container()
        {

            $app = $this->newApplication();
            $app->runningUnitTest();

            $app->boot();

            $this->assertEquals('bar', $app->config('app.foo'));


        }

        /** @test */
        public function users_can_register_service_providers()
        {

            $app = $this->newApplication();
            $app->runningUnitTest();

            $app->boot();

            $this->assertEquals('bar', $app->container()['foo']);
            $this->assertEquals('bar_bootstrapped', $app->container()['foo_bootstrapped']);


        }

        /** @test */
        public function custom_container_adapters_can_be_used()
        {

            $container = new TestContainer();

            $app = new Application($container);

            $this->assertSame($container, $app->container());
            $this->assertInstanceOf(TestContainer::class, $app->container());


        }

        /** @test */
        public function config_values_can_be_retrieved()
        {

            $app = $this->newApplication();
            $app->runningUnitTest();

            $app->boot();

            $this->assertInstanceOf(
                Config::class,
                $app->resolve(Config::class)
            );
            $this->assertSame('bar', $app->config('app.foo'));
            $this->assertSame('boo', $app->config('app.bar.baz'));
            $this->assertSame('bogus_default', $app->config('app.bogus', 'bogus_default'));


        }

        /** @test */
        public function the_request_is_captured()
        {

            $app = $this->newApplication();

            $app->boot();

            $this->assertInstanceOf(Request::class, $app->resolve(Request::class));

        }

        /** @test */
        public function settingUnitTest () {

            $app = $this->newApplication();
            $this->assertFalse($app->isRunningUnitTest());

            $app->runningUnitTest();

            $this->assertTrue($app->isRunningUnitTest());


        }

        /** @test */
        public function testConfigPath () {

            $app = $this->newApplication();
            $path = $app->configPath();

            $this->assertSame($this->base_path . DS . 'config', $path);

            $path = $app->configPath('auth');

            $this->assertSame($this->base_path.DS.'config'.DS.'auth', $path);

        }

        /** @test */
        public function testStoragePath () {

            $app = $this->newApplication();
            $app->boot();

            $path = $app->storagePath();

            $this->assertSame($this->base_path . DS . 'storage', $path);

            $path = $app->storagePath('framework');

            $this->assertSame($this->base_path.DS.'storage'.DS.'framework', $path);

        }

        /** @test */
        public function generateKey () {

            $key = Application::generateKey();

            $this->assertStringStartsWith('base64:', $key);

            try {
                $encryptor = new Encryptor($key);
                $this->assertTrue(true);
            } catch (\Throwable $e ) {
                $this->fail('Generated app key is not compatible.' . PHP_EOL . $e->getMessage());
            }

        }

        private function newApplication() : Application
        {

            $app = Application::create($this->base_path, $this->container);
            $app->setResponseFactory($this->psrResponseFactory());
            $app->setUriFactory($this->psrUriFactory());
            $app->setStreamFactory($this->psrStreamFactory());
            $app->setUploadedFileFactory($this->psrUploadedFileFactory());
            $app->setServerRequestFactory($this->psrServerRequestFactory());

            return $app;

        }

    }


