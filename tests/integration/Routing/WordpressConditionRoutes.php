<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\fixtures\Middleware\WebMiddleware;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreatesWpUrls;
    use Tests\TestCase;
    use BetterWP\Events\Event;
    use BetterWP\Events\ResponseSent;
    use BetterWP\Http\HttpKernel;

    class WordpressConditionRoutes extends TestCase
    {

        /** @test */
        public function its_possible_to_create_a_route_without_url_conditions () {

            $GLOBALS['test']['pass_fallback_route_condition'] = true;
            Event::fake([ResponseSent::class]);

            $response = $this->get('/post1');
            $response->assertOk();
            $response->assertSee('get_fallback');

            Event::assertDispatched(ResponseSent::class);

        }

        /** @test */
        public function if_no_route_matches_due_to_failed_wp_conditions_a_null_response_is_returned()
        {

            $GLOBALS['test']['pass_fallback_route_condition'] = false;
            Event::fake([ResponseSent::class]);

            $this->post('/post1')->assertNullResponse();

            Event::assertNotDispatched(ResponseSent::class);

        }

        /** @test */
        public function if_no_route_matches_due_to_different_http_verbs_a_null_response_is_returned()
        {

            $GLOBALS['test']['pass_fallback_route_condition'] = true;
            Event::fake([ResponseSent::class]);

            $this->delete('/post1')->assertNullResponse();

            Event::assertNotDispatched(ResponseSent::class);

        }

        /** @test */
        public function routes_with_wordpress_conditions_can_have_middleware () {

            $GLOBALS['test']['pass_fallback_route_condition'] =true;
            $GLOBALS['test'][WebMiddleware::run_times] = 0;
            Event::fake([ResponseSent::class]);

            $this->patch('/post1')->assertSee('patch_fallback');

            Event::assertDispatched(ResponseSent::class );
            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times], 'Middleware was not run as expected.');

        }


    }


