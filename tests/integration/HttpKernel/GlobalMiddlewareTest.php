<?php


    declare(strict_types = 1);


    namespace Tests\integration\HttpKernel;

    use Tests\IntegrationTest;
    use Tests\stubs\GlobalMiddleware\UnauthorizedMiddleware;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\DoShutdown;

    class GlobalMiddlewareTest extends IntegrationTest
    {

        /** @test */
        public function global_middleware_can_be_run () {


            $this->newTestApp([
                'always_run_middleware' => true,
                'middleware' => [
                    'groups' => [

                        'global' => [UnauthorizedMiddleware::class]

                    ]
                ]
            ]);

            ob_start();

            ApplicationEvent::fake([DoShutdown::class]);
            ApplicationEvent::dispatch('init');

            $output = ob_get_clean();

            $this->assertSame('Unauthorized', $output);



        }

        /** @test */
        public function the_script_is_terminated_if_the_global_middleware_stack_returns_anything_but_a_null_response () {


            $this->newTestApp([
                'always_run_middleware' => true,
                'middleware' => [
                    'groups' => [

                        'global' => [UnauthorizedMiddleware::class]

                    ]
                ]
            ]);

            ob_start();

            ApplicationEvent::fake([DoShutdown::class]);
            ApplicationEvent::dispatch('init');

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
                           [ UnauthorizedMiddleware::class, false ]
                        ]

                    ]
                ]
            ]);

            ob_start();

            ApplicationEvent::fake([DoShutdown::class]);
            ApplicationEvent::dispatch('init');

            $output = ob_get_clean();

            $this->assertSame('', $output);

            ApplicationEvent::assertNotDispatched(DoShutdown::class);

        }

    }