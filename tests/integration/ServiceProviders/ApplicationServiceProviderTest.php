<?php


	declare( strict_types = 1 );


	namespace Tests\integration\ServiceProviders;

	use Mockery;
    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ErrorHandlerInterface;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Facade\WP;
	use WPEmerge\Facade\WpFacade;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Http\Responses\RedirectResponse;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Session\SessionServiceProvider;
    use WPEmerge\Session\Session;
    use WPEmerge\Support\Url;

    class ApplicationServiceProviderTest extends TestCase {

        protected function setUp() : void
        {
            $this->afterApplicationCreated(function () {
                $this->loadRoutes();
            });
            parent::setUp();

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

            $expected = '/alias/get';
            $this->assertSame($expected, TestApp::routeUrl('alias.get'));

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


            ob_start();
            TestApp::render('view');

            $this->assertSame('Foobar', ob_get_clean());

        }

        /** @test */
        public function a_nested_view_can_be_included()
        {

            $view = TestApp::view('subview.php');

            $this->assertSame('Hello World', $view->toString());

        }

        /** @test */
        public function the_session_can_be_aliased () {

            $this->newTestApp([
                'session' => [
                    'enabled'=>true,
                ],
                'providers' => [
                    SessionServiceProvider::class
                ]
            ]);

            $this->assertInstanceOf(Session::class, TestApp::session());

        }

        /** @test */
        public function the_response_cookies_can_be_aliased () {

            $this->newTestApp();

            $this->assertInstanceOf(Cookies::class, TestApp::cookies());

        }

        /** @test */
        public function a_method_override_field_can_be_outputted () {

            $this->newTestApp();

            $html = TestApp::methodField('PUT');

            $this->assertStringStartsWith('<input', $html);
            $this->assertStringContainsString('PUT', $html);

        }

        /** @test */
        public function the_url_generator_can_be_aliased () {

            $this->newTestApp();

            $this->assertInstanceOf(UrlGenerator::class, TestApp::url());

        }

        /** @test */
        public function the_response_factory_can_be_aliased () {

            $this->newTestApp();

            $this->assertInstanceOf(ResponseFactory::class, TestApp::response());


        }

        /** @test */
        public function a_redirect_response_can_be_created_as_an_alias () {

            $this->newTestApp();

            $this->assertInstanceOf(RedirectResponse::class, TestApp::redirect('/foo'));
            $this->assertInstanceOf(Redirector::class, TestApp::redirect());

        }



	}

