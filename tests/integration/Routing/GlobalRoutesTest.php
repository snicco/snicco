<?php


    declare(strict_types = 1);


    namespace Tests\integration\Routing;

    use Tests\IntegrationTest;
    use Tests\stubs\HeaderStack;
    use Tests\stubs\TestRequest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\IncomingGlobalRequest;
    use WPEmerge\Events\ResponseSent;
    use WPEmerge\Support\Arr;

    class GlobalRoutesTest extends IntegrationTest
    {


        /** @test */
        public function global_routes_are_run_as_soon_as_a_route_file_with_the_name_global_is_auto_discovered () {

            $this->newTestApp(TEST_CONFIG);

            $request = TestRequest::from('GET', 'globals/foo');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('FOO_GLOBAL', ob_get_clean());

        }

        /** @test */
        public function packages_can_register_more_global_routes_files_which_are_not_overwritten () {

            $this->newTestApp(array_merge(TEST_CONFIG, [
                'providers'=> [
                    \Tests\fixtures\RoutingDefinitionServiceProvider::class
                ]
            ]));

            $request = TestRequest::from('GET', 'globals/foo');
            $this->rebindRequest($request);

            ob_start();
            do_action('init');
            $this->assertSame('FOO_GLOBAL', ob_get_clean());


            $request = TestRequest::from('GET', 'other-globals/foo');
            $this->rebindRequest($request);

            ob_start();
            do_action('init');
            $this->assertSame('OTHER_GLOBALS_FOO', ob_get_clean());



        }

        /** @test */
        public function the_script_is_shut_down_if_anything_but_a_null_response_is_returned () {

            $this->newTestApp(TEST_CONFIG);

            $request = TestRequest::from('GET', 'globals/foo');
            $this->rebindRequest($request);

            ob_start();

            ApplicationEvent::fake([ResponseSent::class]);

            do_action('init');

            $this->assertSame('FOO_GLOBAL', ob_get_clean());
            ApplicationEvent::assertDispatched(ResponseSent::class, function (ResponseSent $event) {

                return $event->request->getType() === IncomingGlobalRequest::class;

            });
            HeaderStack::assertHasStatusCode(200);



        }

        /** @test */
        public function the_script_is_not_shut_down_for_null_responses () {

            $this->newTestApp(TEST_CONFIG);
            $request = TestRequest::from('GET', 'globals/bogus');
            $this->rebindRequest($request);
            ApplicationEvent::fake();

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();
            ApplicationEvent::assertNotDispatched(ResponseSent::class);

        }

        /** @test */
        public function global_routes_are_not_run_for_the_native_wordpress_REST_API () {

            $this->newTestApp(TEST_CONFIG);

            $request = TestRequest::from('GET', 'wp-json/posts');
            $this->rebindRequest($request);

            ob_start();

            do_action('init');

            $this->assertSame('', ob_get_clean());
            HeaderStack::assertHasNone();


        }

    }

