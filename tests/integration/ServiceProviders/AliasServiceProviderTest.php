<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\IntegrationTest;
    use Tests\traits\AssertsResponse;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ViewInterface;
    use WPEmerge\Routing\Router;
    use WPEmerge\Support\Url;
    use WPEmerge\Support\VariableBag;

    class AliasServiceProviderTest extends IntegrationTest
    {

        use AssertsResponse;


        protected function setUp() : void
        {

            parent::setUp();

            $this->app = TestApp::make();
            TestApp::boot();

        }

        protected function tearDown() : void
        {

            parent::tearDown();
            TestApp::setApplication(null);
            ApplicationEvent::setInstance(null);

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

            TestApp::route()->get('foo')->name('foo');

            $expected = Url::addTrailing(SITE_URL).'foo';

            $this->assertSame($expected, trim(TestApp::routeUrl('foo'), '/'));

        }

        /** @test */
        public function a_post_route_can_be_aliased()
        {

            TestApp::post('foo', function () {

                return 'foo';

            });

            $response = TestApp::route()->runRoute(TestRequest::from('POST', 'foo'));

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function a_get_route_can_be_aliased()
        {

            TestApp::get('foo', function () {

                return 'foo';
            });

            $response = TestApp::route()->runRoute(TestRequest::from('GET', 'foo'));

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function a_patch_route_can_be_aliased()
        {

            TestApp::patch('foo', function () {

                return 'foo';
            });

            $response = TestApp::route()->runRoute(TestRequest::from('PATCH', 'foo'));

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function a_put_route_can_be_aliased()
        {

            TestApp::put('foo', function () {

                return 'foo';
            });

            $response = TestApp::route()->runRoute(TestRequest::from('PUT', 'foo'));

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function an_options_route_can_be_aliased()
        {

            TestApp::options('foo', function () {

                return 'foo';
            });

            $response = TestApp::route()->runRoute(TestRequest::from('OPTIONS', 'foo'));

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function a_delete_route_can_be_aliased()
        {

            TestApp::delete('foo', function () {

                return 'foo';
            });

            $response = TestApp::route()->runRoute(TestRequest::from('DELETE', 'foo'));

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function a_match_route_can_be_aliased()
        {

            TestApp::match(['GET', 'POST'], 'foo', function () {

                return 'foo';
            });

            $response = TestApp::route()->runRoute(TestRequest::from('GET', 'foo'));
            $this->assertOutput('foo', $response);

            $response = TestApp::route()->runRoute(TestRequest::from('POST', 'foo'));
            $this->assertOutput('foo', $response);


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

            $this->newTestApp( ['views' => [
                TESTS_DIR.DS.'views',
                TESTS_DIR.DS.'views'.DS.'subdirectory',
            ]]);


            $this->assertInstanceOf(ViewInterface::class, TestApp::view('view'));

        }

        /** @test */
        public function a_view_can_be_rendered_and_echoed()
        {

            $this->newTestApp( ['views' => [
                TESTS_DIR.DS.'views',
                TESTS_DIR.DS.'views'.DS.'subdirectory',
            ]]);


            ob_start();
            TestApp::render('view');


            $this->assertSame('Foobar', ob_get_clean());

        }

        /** @test */
        public function a_nested_view_can_be_included()
        {

            $this->newTestApp( ['views' => [
                TESTS_DIR.DS.'views',
                TESTS_DIR.DS.'views'.DS.'subdirectory',
            ]]);

            $view = TestApp::view('subview.php');

            $this->assertSame('Hello World', $view->toString());

        }

    }