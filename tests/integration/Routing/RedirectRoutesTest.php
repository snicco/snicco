<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;

    class RedirectRoutesTest extends IntegrationTest
    {



        /** @test */
        public function redirect_routes_are_run_on_the_init_hook_if_a_valid_redirect_file_is_provided () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', '/location-a'));

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/location-b');

        }

        /** @test */
        public function the_script_will_be_shut_down_if_a_redirect_route_matches () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);
            ApplicationEvent::fake([ResponseSent::class]);
            $this->rebindRequest(TestRequest::from('GET', '/location-a'));

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/location-b');

            ApplicationEvent::assertDispatched(function (ResponseSent $event) {

                return $event->response instanceof RedirectResponse;

            });

        }

        /** @test */
        public function non_redirect_routes_are_not_run_on_the_init_hook_even_if_the_route_would_have_matched () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);

            $this->rebindRequest(TestRequest::from('GET', '/foo'));

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();


        }

        /** @test */
        public function if_no_redirect_route_matches_standard_routes_can_still_run_on_a_later_hook () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR,
                ]
            ]);
            ApplicationEvent::fake([ResponseSent::class]);

            $this->rebindRequest(TestRequest::from('GET', '/foo'));

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();

            ApplicationEvent::assertNotDispatched(ResponseSent::class);

            ob_start();

            apply_filters('template_include', 'wordpress_template');

            $this->assertSame('foo', ob_get_clean());
            HeaderStack::assertHasStatusCode(200);

        }

    }