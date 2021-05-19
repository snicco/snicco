<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\stubs\Conditions\IsPost;
    use Tests\stubs\TestRequest;
    use Tests\traits\AssertsResponse;
    use Tests\traits\SetUpRouter;
    use Tests\UnitTest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Request;

    class FallbackRouteTest extends UnitTest
    {

        use SetUpRouter;
        use AssertsResponse;

        protected function beforeTestRun()
        {

            $this->newRouter($c = $this->createContainer());
            WP::setFacadeContainer($c);

        }

        protected function beforeTearDown()
        {

            WP::setFacadeContainer(null);
            WP::clearResolvedInstances();
            Mockery::close();
        }

        /** @test */
        public function for_web_request_the_fallback_route_controller_evaluates_all_routes_with_WP_conditions_and_no_url()
        {

            $this->router->get()->where(IsPost::class, true)
                         ->handle(function () {

                             return 'FOO';

                         });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'post1');
            $response = $this->router->runRoute($request);
            $this->assertOutput('FOO', $response);


        }

        /** @test */
        public function routes_that_do_have_a_wordpress_condition_AND_a_url_pattern_are_discarded () {

            $this->router->get('post2')->where(IsPost::class, true)
                         ->handle(function () {

                             return 'FOO';

                         });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'post1');
            $response = $this->router->runRoute($request);
            $this->assertNullResponse( $response);

            $request = TestRequest::from('GET', 'post2');
            $response = $this->router->runRoute($request);
            $this->assertOutput('FOO', $response);

        }

        /** @test */
        public function users_can_create_a_custom_fallback_route_that_gets_run_if_the_inbuilt_controller_could_not_resolve_a_valid_wp_condition_route () {

            $this->router->get()->where(IsPost::class, false)
                         ->handle(function () {

                             return 'FOO';

                         });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'post1');
            $response = $this->router->runRoute($request);
            $this->assertNullResponse( $response);

            // now with fallback route
            $router = $this->newRouter();
            $router->fallback( function ( Request $request ) {

                return 'FOO';

            });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'post1');
            $response = $this->router->runRoute($request);
            $this->assertOutput('FOO', $response);

        }




    }