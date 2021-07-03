<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreateTestSubjects;
    use Tests\UnitTest;
    use Tests\fixtures\Middleware\BarMiddleware;
    use Tests\fixtures\Middleware\BazMiddleware;
    use Tests\fixtures\Middleware\FooBarMiddleware;
    use Tests\fixtures\Middleware\FooMiddleware;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Support\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Middleware\Core\RouteRunner;
    use WPEmerge\Routing\Router;

    class RouteMiddlewareTest extends UnitTest
    {

        use CreateTestSubjects;
        use CreateDefaultWpApiMocks;

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

        /** @var RouteRunner */
        private $route_runner;

        protected function beforeTestRun()
        {

            $this->container = $this->createContainer();
            $this->routes = $this->newRouteCollection();
            ApplicationEvent::make($this->container);
            ApplicationEvent::fake();
            WP::setFacadeContainer($this->container);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function applying_a_route_group_to_a_route_applies_all_middleware_in_the_group()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware('foobar');

            });

            $kernel = $this->newKernel([
                'foobar' => [
                    FooMiddleware::class,
                    BarMiddleware::class,

                ],
            ]);

            $request = $this->webRequest('GET', '/foo');

            $output = $this->runKernelAndGetOutput($request, $kernel);

            $this->assertSame('foobar', $output);


        }

        /** @test */
        public function middleware_in_the_global_group_is_always_applied()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                });


            });

            $request = $this->webRequest('GET', '/foo');

            $kernel = $this->newKernel([
                'global' => [
                    FooMiddleware::class,
                    BarMiddleware::class,

                ],
            ]);

            $output = $this->runKernelAndGetOutput($request, $kernel);

            $this->assertSame('foobar', $output);

        }

        /** @test */
        public function duplicate_middleware_is_filtered_out()
        {

            $this->createRoutes(function () {

                $this->router->middleware('foobar')->get('/foo', function (Request $request) {

                    return $request->body;

                });


            });

            $kernel = $this->newKernel(
                [
                    'global' => [
                        FooMiddleware::class,
                        BarMiddleware::class,
                    ],
                    'foobar' => [
                        FooMiddleware::class,
                        BarMiddleware::class,
                    ],

                ]);

            $request = $this->webRequest('GET', '/foo');

            $this->assertSame('foobar', $this->runKernelAndGetOutput($request, $kernel));


        }

        /** @test */
        public function duplicate_middleware_is_filtered_out_when_passing_the_same_middleware_arguments()
        {


            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware(['all', 'foo:FOO']);


            });

            $kernel = $this->newKernel([
                'all' => [
                    FooMiddleware::class.':FOO',
                    BarMiddleware::class,
                    BazMiddleware::class,
                ],
            ]);

            $request = $this->webRequest('GET', 'foo');
            $this->assertSame('FOObarbaz', $this->runKernelAndGetOutput($request, $kernel));

        }

        /** @test */
        public function multiple_middleware_groups_can_be_applied()
        {

            $this->createRoutes(function () {

                $this->router->middleware('foo', 'bar')
                             ->get('/foo', function (Request $request) {

                                 return $request->body;

                             });

            });

            $kernel = $this->newKernel([
                'foo' => [
                    FooMiddleware::class,
                ],
                'bar' => [
                    BarMiddleware::class,
                ],
            ]);

            $request = $this->webRequest('GET', '/foo');

            $this->assertSame('foobar', $this->runKernelAndGetOutput($request, $kernel));

        }

        /** @test */
        public function unknown_middleware_throws_an_exception()
        {

            $this->expectExceptionMessage('Unknown middleware [abc]');

            $this->createRoutes(function () {

                $this->router->middleware('abc')->get('foo', function (Request $request) {

                    return $request->body;

                });

            });

            $kernel = $this->newKernel();
            $kernel->run($this->webRequest('GET', 'foo'));



        }

        /** @test */
        public function multiple_middleware_arguments_can_be_passed()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })
                             ->middleware('foobar');

                $this->router->post('/foo', function (Request $request) {

                    return $request->body;

                })
                             ->middleware('foobar:FOO');

                $this->router->patch('/foo', function (Request $request) {

                    return $request->body;

                })
                             ->middleware('foobar:FOO,BAR');

            });



            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('foobar', $this->runKernelAndGetOutput($request));

            $request = $this->webRequest('POST', '/foo');
            $this->assertSame('FOObar', $this->runKernelAndGetOutput($request));

            $request = $this->webRequest('PATCH', '/foo');
            $this->assertSame('FOOBAR', $this->runKernelAndGetOutput($request));

        }

        /** @test */
        public function a_middleware_group_can_point_to_a_middleware_alias()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware('foogroup');

            });

           $kernel = $this->newKernel([

               'foogroup' => [
                   'foo'
               ]

           ]);

            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('foo', $this->runKernelAndGetOutput($request, $kernel));

        }

        /** @test */
        public function group_and_route_middleware_can_be_combined()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware(['baz', 'foobar']);

            });

            $kernel = $this->newKernel([
                'foobar' => [
                    FooMiddleware::class,
                    BarMiddleware::class,
                ]
            ]);

            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('bazfoobar', $this->runKernelAndGetOutput($request, $kernel));


        }

        /** @test */
        public function a_middleware_group_can_contain_another_middleware_group()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware('baz_group');


            });

            $kernel = $this->newKernel([

                'baz_group' => [
                    BazMiddleware::class,
                    'bar_group',
                ],
                'bar_group' => [
                    BarMiddleware::class,
                    'foo_group',
                ],
                'foo_group' => [
                    FooMiddleware::class,
                ]

            ]);

            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('bazbarfoo', $this->runKernelAndGetOutput($request, $kernel));


        }

        /** @test */
        public function middleware_can_be_applied_without_an_alias()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware(FooBarMiddleware::class.':FOO,BAR');

            });


            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('FOOBAR', $this->runKernelAndGetOutput($request));


        }

        /**
         *
         *
         *
         *
         * SORTING
         *
         *
         *
         *
         */

        /** @test */
        public function non_global_middleware_can_be_sorted()
        {

            $this->createRoutes(function () {

                $this->router->middleware('barbaz')
                             ->group(function () {

                    $this->router->get('/foo', function (Request $request) {

                        return $request->body;

                    })->middleware(FooMiddleware::class);

                });

            });

            $kernel = $this->newKernel([
                'barbaz' => [
                    BazMiddleware::class,
                    BarMiddleware::class,
                ]
            ]);

            $this->withMiddlewarePriority([

                FooMiddleware::class,
                BarMiddleware::class,
                BazMiddleware::class,

            ]);

            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('foobarbaz', $this->runKernelAndGetOutput($request, $kernel));

        }

        /** @test */
        public function middleware_keeps_its_relative_position_if_its_has_no_priority_defined()
        {

            $this->createRoutes(function () {

                $this->router->get('/foo', function (Request $request) {

                    return $request->body;

                })->middleware('all');


            });

            $kernel = $this->newKernel([
                'all' => [
                    FooBarMiddleware::class,
                    BarMiddleware::class,
                    BazMiddleware::class,
                    FooMiddleware::class,
                ]
            ]);

            $this->withMiddlewarePriority([

                FooMiddleware::class,
                BarMiddleware::class,

            ]);

            $request = $this->webRequest('GET', '/foo');
            $this->assertSame('foobarfoobarbaz', $this->runKernelAndGetOutput($request,$kernel));

        }

        private function withMiddlewarePriority(array $array)
        {
            $this->middleware_stack->middlewarePriority($array);
        }


    }