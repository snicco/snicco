<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\stubs\Middleware\WebMiddleware;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\traits\CreateWpTestUrls;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\HttpKernel;

    class WordpressConditionRoutes extends IntegrationTest
    {

        use CreateWpTestUrls;

        /**
         * @var HttpKernel
         */
        private $kernel;

        protected function setUp() : void
        {
            parent::setUp();
            $this->newTestApp(TEST_CONFIG);
            $this->kernel = TestApp::resolve(HttpKernel::class);

        }

        /** @test */
        public function its_possible_to_create_routes_that_dont_match_an_url()
        {

            ApplicationEvent::fake([ResponseSent::class]);

            $this->seeKernelOutput('get_fallback', TestRequest::from('GET', 'post1'));

            ApplicationEvent::assertDispatchedTimes(ResponseSent::class, 1 );


        }

        /** @test */
        public function if_no_route_matches_due_to_failed_wp_conditions_a_null_response_is_returned()
        {

            ApplicationEvent::fake([ResponseSent::class]);


            $this->seeKernelOutput('', TestRequest::from('POST', 'post1'));


            ApplicationEvent::assertNotDispatched(ResponseSent::class);


        }

        /** @test */
        public function if_no_route_matches_due_to_different_http_verbs_a_null_response_is_returned()
        {

            ApplicationEvent::fake([ResponseSent::class]);


            $this->seeKernelOutput('', TestRequest::from('DELETE', 'post1'));


            ApplicationEvent::assertNotDispatched(ResponseSent::class);

        }

        /** @test */
        public function routes_with_wordpress_conditions_can_have_middleware () {

            $GLOBALS['test'][WebMiddleware::run_times] = 0;
            ApplicationEvent::fake([ResponseSent::class]);

            $this->seeKernelOutput('patch_fallback', TestRequest::from('PATCH', 'post1'));

            ApplicationEvent::assertDispatchedTimes(ResponseSent::class, 1 );
            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times], 'Middleware was not run as expected.');

        }


    }


