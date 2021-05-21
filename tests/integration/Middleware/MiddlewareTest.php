<?php


    declare(strict_types = 1);


    namespace Tests\integration\Middleware;

    use Tests\IntegrationTest;
    use Tests\stubs\Middleware\BarMiddleware;
    use Tests\stubs\Middleware\FooBarMiddleware;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use WPEmerge\Events\IncomingWebRequest;
    use WPEmerge\Http\HttpKernel;
    use WPEmerge\Middleware\RoutingMiddleware;
    use WPEmerge\Middleware\RouteRunner;

    class MiddlewareTest extends IntegrationTest
    {

        /** @test */
        public function playground()
        {

            $this->newTestApp([

                'always_run_middleware' => false,
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ],
                'middleware' => [

                    'groups' => [

                        'global' => [
                            RoutingMiddleware::class,
                            BarMiddleware::class,
                            RouteRunner::class,

                        ],
                    ],

                ],

            ]);

            ob_start();

            $request_event = new IncomingWebRequest('wordpress.php', TestRequest::from('GET', 'foo_middleware'));

            /** @var HttpKernel $kernel */
            $kernel = TestApp::resolve(HttpKernel::class);

            $kernel->alwaysWithGlobalMiddleware();

            $kernel->run($request_event);

            $output = ob_get_clean();
            $this->assertSame('foo', $output);


        }

    }