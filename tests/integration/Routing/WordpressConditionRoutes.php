<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\traits\AssertsResponse;
    use Tests\traits\CreateWpTestUrls;
    use WPEmerge\Contracts\ConditionInterface;
    use WPEmerge\Http\Request;
    use WPEmerge\Routing\Router;

    class WordpressConditionRoutes extends IntegrationTest
    {

        use CreateWpTestUrls;
        use AssertsResponse;

        /**
         * @var Router
         */
        private $router;

        protected function setUp() : void
        {

            parent::setUp();
            $this->newTestApp();
            $this->router = TestApp::resolve(Router::class);
        }

        /** @test */
        public function its_possible_to_create_routes_that_dont_match_an_url()
        {

            $this->router->get()->where(\Tests\stubs\Conditions\IsPost::class, true)->handle(function () {

                return 'FOO';

            });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();


            $request = TestRequest::from('GET', 'whatever');

            $response = $this->router->runRoute($request);

            $this->assertOutput('FOO', $response);


        }

        /** @test */
        public function if_no_route_matches_due_to_failed_wp_conditions_a_null_response_is_returned()
        {


            $this->router->get()->where(\Tests\stubs\Conditions\IsPost::class, false)->handle(function () {

                return 'FOO';

            });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('GET', 'whatever');

            $response = $this->router->runRoute($request);

            $this->assertNullResponse($response);

        }

        /** @test */
        public function if_no_route_matches_due_to_different_http_verbs_a_null_response_is_returned()
        {


            $this->router->get()->where(\Tests\stubs\Conditions\IsPost::class, true)->handle(function () {

                return 'FOO';

            });

            $this->router->createFallbackWebRoute();
            $this->router->loadRoutes();

            $request = TestRequest::from('POST', 'whatever');

            $response = $this->router->runRoute($request);

            $this->assertNullResponse($response);

        }


    }


