<?php


    declare(strict_types = 1);


    namespace Tests\integration\Http;

    use Tests\IntegrationTest;
    use Tests\stubs\Middleware\GlobalMiddleware;
    use Tests\stubs\Middleware\WebMiddleware;
    use Tests\stubs\TestRequest;

    class HttpKernelMiddlewareTest extends IntegrationTest
    {

        /** @test */
        public function custom_middleware_groups_can_be_defined () {

            $GLOBALS['test'][WebMiddleware::run_times] = 0;

            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR . DS . 'stubs' .DS .  'routes'
                ],
                'middleware' => [
                    'groups' => [
                        'custom_group' => [
                            WebMiddleware::class
                        ]
                    ]
                ]
            ]);

            $this->seeKernelOutput('foo', TestRequest::from('GET', 'middleware/foo'));
            $this->assertSame(1, $GLOBALS['test'][WebMiddleware::run_times]);

        }



    }