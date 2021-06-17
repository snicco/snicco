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

            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', '/location-a'));

            ob_start();

            apply_filters('template_include', 'wordpress.php');

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
            $this->registerAndRunApiRoutes();
            $this->rebindRequest(TestRequest::from('GET', '/location-a'));

            ob_start();

            apply_filters('template_include', 'wordpress.php');

            $this->assertSame('', ob_get_clean());

            HeaderStack::assertHasStatusCode(302);
            HeaderStack::assertContains('Location', '/location-b');

            ApplicationEvent::assertDispatched(function (ResponseSent $event) {

                return $event->response instanceof RedirectResponse;

            });

        }


    }