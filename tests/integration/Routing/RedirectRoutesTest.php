<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use Tests\TestCase;
    use WPEmerge\Application\Application;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Http\Responses\RedirectResponse;

    class RedirectRoutesTest extends TestCase
    {

        /** @test */
        public function the_script_will_be_shut_down_if_a_redirect_route_matches () {


            ApplicationEvent::fake([ResponseSent::class]);

            $this->get('/location-a')->assertRedirect('/location-b');

            ApplicationEvent::assertDispatched(function (ResponseSent $event) {

                return $event->response instanceof RedirectResponse;

            });

        }


    }