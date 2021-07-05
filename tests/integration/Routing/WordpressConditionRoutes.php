<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreatesWpUrls;
    use Tests\TestCase;
    use WPMvc\Application\ApplicationEvent;
    use WPMvc\Events\ResponseSent;
    use WPMvc\Http\HttpKernel;

    class WordpressConditionRoutes extends TestCase
    {

        /** @test */
        public function its_possible_to_create_a_route_without_url_conditions () {

            $GLOBALS['test']['pass_fallback_route_condition'] = true;
            ApplicationEvent::fake([ResponseSent::class]);

            $response = $this->get('/post1');
            $response->assertOk();
            $response->assertSee('get_fallback');

            ApplicationEvent::assertDispatched(ResponseSent::class);

        }

        /** @test */
        public function if_no_route_matches_due_to_failed_wp_conditions_a_null_response_is_returned()
        {

            $GLOBALS['test']['pass_fallback_route_condition'] = false;
            ApplicationEvent::fake([ResponseSent::class]);

            $this->post('/post1')->assertNullResponse();

            ApplicationEvent::assertNotDispatched(ResponseSent::class);

        }

        /** @test */
        public function if_no_route_matches_due_to_different_http_verbs_a_null_response_is_returned()
        {

            $GLOBALS['test']['pass_fallback_route_condition'] = true;
            ApplicationEvent::fake([ResponseSent::class]);

            $this->delete('/post1')->assertNullResponse();

            ApplicationEvent::assertNotDispatched(ResponseSent::class);

        }

        /** @test */
        public function routes_with_wordpress_conditions_can_have_middleware () {

            $GLOBALS['test']['pass_fallback_route_condition'] =true;
            $GLOBALS['test'][WebMiddleware::run_times] = 0;
            ApplicationEvent::fake([ResponseSent::class]);

            $this->patch('/post1')->assertSee('patch_fallback');

            ApplicationEvent::assertDispatched(ResponseSent::class );
            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times], 'Middleware was not run as expected.');

        }


    }


