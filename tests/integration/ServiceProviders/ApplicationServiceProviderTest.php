<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use Mockery;
    use Tests\integration\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Facade\WP;
	use WPEmerge\Facade\WpFacade;
    use WPEmerge\Routing\Router;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\SessionStore;
    use WPEmerge\Support\Url;
    use WPEmerge\Support\VariableBag;

    class ApplicationServiceProviderTest extends IntegrationTest {


        protected function setUp() : void
        {

            parent::setUp();

            $this->app = $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
            ]);


        }

        protected function tearDown() : void
        {

            parent::tearDown();
            TestApp::setApplication(null);
            ApplicationEvent::setInstance(null);

        }

		/** @test */
		public function the_wp_facade_has_the_correct_container() {

			$container = TestApp::container();

			$this->assertSame( $container, WpFacade::getFacadeContainer() );

		}

		/** @test */
		public function the_facade_can_be_swapped_during_test() {

			WP::shouldReceive( 'isAdmin' )->andReturn( true );

			$this->assertTrue( WP::isAdmin() );

            Mockery::close();
            WP::reset();

        }

		/** @test */
		public function the_error_handler_gets_unregistered_by_default_after_booting_the_app () {

		    $this->newTestApp([
		        'providers'=> [
		            NoGlobalExceptions::class,
                ]
            ]);

		    $this->assertTrue(true);

		    Mockery::close();
            WP::reset();


		}

        /** @test */
		public function the_error_handler_can_be_registered_globally () {

		    $this->newTestApp([
		        'providers'=> [
		            GlobalExceptions::class,
                ],
                'exception_handling' => [
                    'global' => true
                ]
            ]);

		    $this->assertTrue(true);

            Mockery::close();
            WP::reset();


        }

        /** @test */
        public function the_application_instance_can_be_aliased()
        {

            $this->assertInstanceOf(Application::class, TestApp::app());
            $this->assertSame($this->app, TestApp::app());


        }

        /** @test */
        public function the_router_can_be_aliased()
        {

            $this->assertInstanceOf(Router::class, TestApp::route());

        }

        /** @test */
        public function a_named_route_url_can_be_aliased()
        {

            $expected = Url::addTrailing(SITE_URL).'alias/get';
            $this->assertSame($expected, trim(TestApp::routeUrl('alias.get'), '/'));

        }

        /** @test */
        public function a_post_route_can_be_aliased()
        {

            $this->seeKernelOutput('post', TestRequest::from('POST', 'alias/post'));


        }

        /** @test */
        public function a_get_route_can_be_aliased()
        {
            $this->seeKernelOutput('get', TestRequest::from('GET', 'alias/get'));

        }

        /** @test */
        public function a_patch_route_can_be_aliased()
        {

            $this->seeKernelOutput('patch', TestRequest::from('PATCH', 'alias/patch'));

        }

        /** @test */
        public function a_put_route_can_be_aliased()
        {

            $this->seeKernelOutput('put', TestRequest::from('PUT', 'alias/put'));

        }

        /** @test */
        public function an_options_route_can_be_aliased()
        {

            $this->seeKernelOutput('options', TestRequest::from('OPTIONS', 'alias/options'));


        }

        /** @test */
        public function a_delete_route_can_be_aliased()
        {

            $this->seeKernelOutput('delete', TestRequest::from('DELETE', 'alias/delete'));


        }

        /** @test */
        public function a_match_route_can_be_aliased()
        {

            $this->seeKernelOutput('', TestRequest::from('DELETE', 'alias/match'));

            $this->seeKernelOutput('match', TestRequest::from('POST', 'alias/match'));




        }

        /** @test */
        public function the_global_variable_bag_can_be_retrieved()
        {

            $this->assertInstanceOf(VariableBag::class, TestApp::globals());

        }

        /** @test */
        public function a_composer_can_be_added_as_an_alias()
        {

            TestApp::addComposer('foo', function () {
                // Assert no exception.
            });

            $this->assertTrue(true);

        }

        /** @test */
        public function a_view_can_be_created_as_an_alias()
        {

            $this->newTestApp([
                'views' => [
                    VIEWS_DIR,
                    VIEWS_DIR.DS.'subdirectory',
                ],
            ]);

            $this->assertInstanceOf(ViewInterface::class, TestApp::view('view'));

        }

        /** @test */
        public function a_view_can_be_rendered_and_echoed()
        {

            $this->newTestApp([
                'views' => [
                    VIEWS_DIR,
                    VIEWS_DIR.DS.'subdirectory',
                ],
            ]);

            ob_start();
            TestApp::render('view');

            $this->assertSame('Foobar', ob_get_clean());

        }

        /** @test */
        public function a_nested_view_can_be_included()
        {

            $this->newTestApp([
                'views' => [
                    VIEWS_DIR,
                    VIEWS_DIR.DS.'subdirectory',
                ],
            ]);

            $view = TestApp::view('subview.php');

            $this->assertSame('Hello World', $view->toString());

        }

        /** @test */
        public function the_session_can_be_aliased () {

            $this->newTestApp([
                'providers' => [
                    SessionServiceProvider::class
                ]
            ]);

            $this->assertInstanceOf(SessionStore::class, TestApp::session());

        }


	}

	class NoGlobalExceptions extends ServiceProvider {

        public function register() : void
        {

            $mock = Mockery::mock(ErrorHandlerInterface::class);

            $mock->shouldReceive('register')->once();
            $mock->shouldReceive('unregister')->once();

            $this->container->instance(ErrorHandlerInterface::class, $mock);

        }

        function bootstrap() : void
        {
        }

    }

	class GlobalExceptions extends ServiceProvider {

        public function register() : void
        {

            $mock = Mockery::mock(ErrorHandlerInterface::class);

            $mock->shouldReceive('register')->once();
            $mock->shouldNotReceive('unregister');

            $this->container->instance(ErrorHandlerInterface::class, $mock);

        }

        function bootstrap() : void
        {
        }

    }