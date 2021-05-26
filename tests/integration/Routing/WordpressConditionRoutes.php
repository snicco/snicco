<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\stubs\TestApp;
    use Tests\traits\CreateWpTestUrls;
    use Tests\traits\TestHelpers;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Routing\Router;

    class WordpressConditionRoutes extends IntegrationTest
    {

        use CreateWpTestUrls;
        use TestHelpers;

        /**
         * @var HttpKernel
         */
        private $kernel;

        protected function setUp() : void
        {
            parent::setUp();
            $this->newTestApp(TEST_CONFIG);
            $this->router = TestApp::resolve(Router::class);
            $this->kernel = TestApp::resolve(HttpKernel::class);
        }

        /** @test */
        public function its_possible_to_create_routes_that_dont_match_an_url()
        {

            $this->expectOutputString('FOO');
            ApplicationEvent::fake([ResponseSent::class]);

            $request = $this->webRequest('GET', 'post1');
            $this->kernel->run($request);
            ApplicationEvent::assertDispatchedTimes(ResponseSent::class, 1 );


        }

        /** @test */
        public function if_no_route_matches_due_to_failed_wp_conditions_a_null_response_is_returned()
        {

            ApplicationEvent::fake([ResponseSent::class]);
            $this->expectOutputString('');

            $request = $this->webRequest('POST', 'post1');
            $this->kernel->run($request);

            ApplicationEvent::assertNotDispatched(ResponseSent::class);


        }

        /** @test */
        public function if_no_route_matches_due_to_different_http_verbs_a_null_response_is_returned()
        {

            ApplicationEvent::fake([ResponseSent::class]);
            $this->expectOutputString('');

            // delete route is not registered for the fallback controller
            $request = $this->webRequest('DELETE', 'post1');
            $this->kernel->run($request);

            ApplicationEvent::assertNotDispatched(ResponseSent::class);

        }


    }


