<?php


    declare(strict_types = 1);


    namespace Tests\integration\HttpKernel;

    use Tests\IntegrationTest;
    use Tests\stubs\GlobalMiddleware\ChangeRequestMiddleware;
    use Tests\stubs\TestApp;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\DoShutdown;
    use WPEmerge\Http\Request;

    class GlobalMiddlewareTest extends IntegrationTest
    {

        /** @test */
        public function global_middleware_can_be_run () {

            $this->newTestApp([
                'always_run_middleware' => true,
                'middleware' => [
                    'groups' => [

                        'global' => [ChangeRequestMiddleware::class]

                    ]
                ]
            ]);

            ob_start();

            ApplicationEvent::fake([DoShutdown::class]);

            do_action('init');

            $output = ob_get_clean();

            $this->assertSame('Unauthorized', $output);



        }

        /** @test */
        public function the_script_is_terminated_if_the_global_middleware_stack_returns_anything_but_a_null_response () {


            $this->newTestApp([
                'always_run_middleware' => true,
                'middleware' => [
                    'groups' => [

                        'global' => [ChangeRequestMiddleware::class]

                    ]
                ]
            ]);

            ob_start();

            ApplicationEvent::fake([DoShutdown::class]);

            do_action('init');


            $output = ob_get_clean();

            $this->assertSame('Unauthorized', $output);

            ApplicationEvent::assertDispatchedTimes(DoShutdown::class, 1);


        }

        /** @test */
        public function no_output_is_sent_if_no_middleware_generates_a_response () {

            $this->newTestApp([
                'always_run_middleware' => true,
                'middleware' => [
                    'groups' => [

                        'global' => [
                           [ ChangeRequestMiddleware::class, false ]
                        ]

                    ]
                ]
            ]);

            ob_start();

            ApplicationEvent::fake([DoShutdown::class]);

            do_action('init');

            $output = ob_get_clean();

            $this->assertSame('', $output);

            ApplicationEvent::assertNotDispatched(DoShutdown::class);

        }

        /** @test */
        public function if_the_request_object_is_modified_in_the_global_middleware_stack_its_saved_again_in_the_container () {

            $this->newTestApp([
                'always_run_middleware' => true,
                'middleware' => [
                    'groups' => [

                        'global' => [ChangeRequestMiddleware::class]

                    ]
                ]
            ]);

            $request_before = TestApp::resolve(Request::class);

            ob_start();
            ApplicationEvent::fake([DoShutdown::class]);

            do_action('init');

            $output = ob_get_clean();
            $this->assertSame('', $output);

            /** @var Request $request_after */
            $request_after = TestApp::resolve(Request::class);
            $this->assertNotSame($request_before, $request_after, 'Request was not updated');
            $this->assertSame('bar', $request_after->getAttribute('foo'));

            ApplicationEvent::assertNotDispatched(DoShutdown::class);

        }



    }