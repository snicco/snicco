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
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\Router;
    use WPEmerge\Support\Url;
    use WPEmerge\Support\VariableBag;

    class AliasServiceProviderTest extends IntegrationTest
    {

        use AssertsResponse;

        protected function setUp() : void
        {

            parent::setUp();

            $this->app = $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes',
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

            $this->seeOutput('post', TestRequest::from('POST', 'alias/post'));


        }

        /**
         * @test
         * see: stubs/Routes/web.php
         */
        public function a_get_route_can_be_aliased()
        {
            $this->seeOutput('get', TestRequest::from('GET', 'alias/get'));

        }

        /**
         * @test
         *
         * see: stubs/Routes/web.php
         *
         */
        public function a_patch_route_can_be_aliased()
        {

            $this->seeOutput('patch', TestRequest::from('PATCH', 'alias/patch'));

        }

        /** @test */
        public function a_put_route_can_be_aliased()
        {

            $this->seeOutput('put', TestRequest::from('PUT', 'alias/put'));

        }

        /** @test */
        public function an_options_route_can_be_aliased()
        {

            $this->seeOutput('options', TestRequest::from('OPTIONS', 'alias/options'));


        }

        /** @test */
        public function a_delete_route_can_be_aliased()
        {

            $this->seeOutput('delete', TestRequest::from('DELETE', 'alias/delete'));


        }

        /** @test */
        public function a_match_route_can_be_aliased()
        {

            $this->seeOutput('', TestRequest::from('DELETE', 'alias/match'));

            $this->seeOutput('match', TestRequest::from('POST', 'alias/match'));




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
                    TESTS_DIR.DS.'views',
                    TESTS_DIR.DS.'views'.DS.'subdirectory',
                ],
            ]);

            $this->assertInstanceOf(ViewInterface::class, TestApp::view('view'));

        }

        /** @test */
        public function a_view_can_be_rendered_and_echoed()
        {

            $this->newTestApp([
                'views' => [
                    TESTS_DIR.DS.'views',
                    TESTS_DIR.DS.'views'.DS.'subdirectory',
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
                    TESTS_DIR.DS.'views',
                    TESTS_DIR.DS.'views'.DS.'subdirectory',
                ],
            ]);

            $view = TestApp::view('subview.php');

            $this->assertSame('Hello World', $view->toString());

        }


    }