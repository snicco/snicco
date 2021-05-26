<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Contracts\ContainerAdapter;
    use Mockery;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\TestHelpers;
    use Tests\UnitTest;
    use Tests\stubs\Conditions\FalseCondition;
    use Tests\stubs\Conditions\TrueCondition;
    use Tests\stubs\Conditions\UniqueCondition;
    use Tests\stubs\Middleware\BarMiddleware;
    use Tests\stubs\Middleware\BazMiddleware;
    use Tests\stubs\Middleware\FooMiddleware;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Facade\WP;
    use WPEmerge\Http\Psr7\Request;
    use WPEmerge\Routing\Router;

    class RouteGroupsTest extends UnitTest
    {

        use TestHelpers;
        use CreateDefaultWpApiMocks;

        const namespace = 'Tests\stubs\Controllers\Web';

        /**
         * @var ContainerAdapter
         */
        private $container;

        /** @var Router */
        private $router;

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



        /**
         *
         *
         *
         *
         *
         * ROUTE GROUPS
         *
         *
         *
         *
         *
         *
         */

        /** @test */
        public function methods_can_be_merged_for_a_group()
        {

            $this->createRoutes(function () {

                $this->router
                    ->methods(['GET', 'PUT'])
                    ->group(function () {

                        $this->router->post('/foo')->handle(function () {

                            return 'post_foo';

                        });

                    });

            });

            $get_request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('post_foo', $get_request);

            $put_request = $this->webRequest('PUT', '/foo');
            $this->runAndAssertOutput('post_foo', $put_request);

            $post_request = $this->webRequest('POST', '/foo');
            $this->runAndAssertOutput('post_foo', $post_request);

            $patch_request = $this->webRequest('PATCH', '/foo');
            $this->runAndAssertEmptyOutput($patch_request);


        }

        /** @test */
        public function middleware_is_merged_for_route_groups()
        {

            $this->createRoutes(function () {

                $this->router
                    ->middleware('foo:FOO')
                    ->group(function () {

                        $this->router
                            ->get('/foo')
                            ->middleware('bar:BAR')
                            ->handle(function (Request $request) {

                                return $request->body;

                            });

                        $this->router
                            ->post('/foo')
                            ->handle(function (Request $request) {

                                return $request->body;

                            });

                    });

            });

            $get_request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('FOOBAR', $get_request);

            $post_request = $this->webRequest('POST', '/foo');
            $this->runAndAssertOutput('FOO', $post_request);


        }

        /** @test */
        public function the_group_namespace_is_applied_to_child_routes()
        {

            $this->createRoutes(function () {

                $this->router
                    ->namespace(self::namespace)
                    ->group(function () {

                        $this->router->get('/foo')->handle('RoutingController@foo');

                    });


            });

            $get_request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $get_request);


        }

        /** @test */
        public function a_group_can_prefix_all_child_route_urls()
        {

            $this->createRoutes(function () {

                $this->router
                    ->prefix('foo')
                    ->group(function () {

                        $this->router->get('bar', function () {

                            return 'foobar';

                        });

                        $this->router->get('baz', function () {

                            return 'foobaz';

                        });


                    });

            });

            $this->runAndAssertOutput('foobar', $this->webRequest('GET', '/foo/bar'));
            $this->runAndAssertOutput('foobaz', $this->webRequest('GET', '/foo/baz'));
            $this->runAndAssertEmptyOutput($this->webRequest('GET', '/foo'));

        }

        /** @test */
        public function group_conditions_are_merged_into_child_routes()
        {

            $this->createRoutes(function () {

                $this->router
                    ->where('maybe', false)
                    ->namespace('Tests\stubs\Controllers\Web')
                    ->group(function () {

                        $this->router
                            ->get('/foo')
                            ->where(new FalseCondition())
                            ->handle('RoutingController@foo');

                        $this->router
                            ->post('/foo')
                            ->where(new TrueCondition())
                            ->handle('RoutingController@foo');

                    });

            });

            $get_request = $this->webRequest('GET', '/foo');
            $this->runAndAssertEmptyOutput($get_request);

            $post_request = $this->webRequest('POST', '/foo');
            $this->runAndAssertEmptyOutput($post_request);


        }

        /** @test */
        public function duplicate_conditions_a_removed_during_route_compilation()
        {

            $this->createRoutes(function () {

                $this->router
                    ->where(new UniqueCondition())
                    ->group(function () {

                        $this->router
                            ->get('/foo', function () {

                                return 'get_foo';

                            })
                            ->where(new UniqueCondition());

                    });

            });

            $this->runAndAssertOutput('get_foo', $this->webRequest('GET', '/foo'));

            $count = $GLOBALS['test']['unique_condition'];
            $this->assertSame(1, $count, 'Condition was run: '.$count.' times.');


        }

        /** @test */
        public function unique_conditions_are_also_enforced_when_conditions_are_aliased()
        {

            $this->createRoutes(function () {

                $this->router
                    ->where('unique')
                    ->group(function () {

                        $this->router
                            ->get('/bar', function () {

                                return 'get_bar';

                            })
                            ->where('unique');

                    });


            });

            $this->runAndAssertOutput('get_bar', $this->webRequest('GET', '/bar'));

            $count = $GLOBALS['test']['unique_condition'];
            $this->assertSame(1, $count, 'Condition was run: '.$count.' times.');


        }

        /**
         *
         *
         *
         *
         *
         * NESTED ROUTE GROUPS
         *
         *
         *
         *
         *
         */

        /** @test */
        public function methods_are_merged_on_multiple_levels()
        {

            $this->createRoutes(function () {

                $this->router
                    ->methods('GET')
                    ->group(function () {

                        $this->router->methods('POST')->group(function () {

                            $this->router->put('/foo')->handle(function () {

                                return 'foo';

                            });

                        });

                        $this->router->patch('/bar')->handle(function () {

                            return 'bar';

                        });

                    });

            });

            // First route
            $post = $this->webRequest('POST', '/foo');
            $this->runAndAssertOutput('foo', $post);

            $put = $this->webRequest('PUT', '/foo');
            $this->runAndAssertOutput('foo', $put);

            $get = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $get);

            $patch = $this->webRequest('PATCH', '/foo');
            $this->runAndAssertEmptyOutput($patch);

            // Second route
            $get = $this->webRequest('GET', '/bar');
            $this->runAndAssertOutput('bar', $get);

            $patch = $this->webRequest('PATCH', '/bar');
            $this->runAndAssertOutput('bar', $patch);

            $post = $this->webRequest('POST', '/bar');
            $this->runAndAssertEmptyOutput($post);

            $put = $this->webRequest('PUT', '/bar');
            $this->runAndAssertEmptyOutput($put);

        }

        /** @test */
        public function middleware_is_nested_on_multiple_levels()
        {


            $this->createRoutes(function () {

                $this->router
                    ->middleware('foo:FOO')
                    ->group(function () {

                        $this->router->middleware('bar:BAR')->group(function () {

                            $this->router
                                ->get('/foo')
                                ->middleware('baz:BAZ')
                                ->handle(function (Request $request) {

                                    return $request->body;

                                });

                        });

                        $this->router
                            ->get('/bar')
                            ->middleware('baz:BAZ')
                            ->handle(function (Request $request) {

                                return $request->body;

                            });

                    });

            });

            $get = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('FOOBARBAZ', $get);

            $get = $this->webRequest('GET', '/bar');
            $this->runAndAssertOutput('FOOBAZ', $get);

        }

        /** @test */
        public function the_route_namespace_is_always_overwritten_by_child_routes()
        {

            /** @todo decide if its desired to overwritte the route namespace. */

            $this->createRoutes(function () {

                $this->router
                    ->namespace('Tests\FalseNamespace')
                    ->group(function () {

                        $this->router
                            ->namespace(self::namespace)
                            ->get('/foo')
                            ->handle('RoutingController@foo');

                    });

            });

            $get_request = $this->webRequest('GET', '/foo');
            $this->runAndAssertOutput('foo', $get_request);


        }

        /** @test */
        public function group_prefixes_are_merged_on_multiple_levels()
        {

            $this->createRoutes(function () {

                $this->router
                    ->prefix('foo')
                    ->group(function () {

                        $this->router->prefix('bar')->group(function () {

                            $this->router->get('baz', function () {

                                return 'foobarbaz';

                            });

                        });

                        $this->router->get('biz', function () {

                            return 'foobiz';

                        });


                    });

            });

            $this->runAndAssertOutput('foobarbaz', $this->webRequest('GET', '/foo/bar/baz'));

            $this->runAndAssertOutput('foobiz', $this->webRequest('GET', '/foo/biz'));

            $this->runAndAssertEmptyOutput($this->webRequest('GET', '/foo/bar/biz'));


        }

        /** @test */
        public function conditions_are_merged_on_multiple_levels()
        {

            // Given
            $GLOBALS['test']['parent_condition_called'] = false;
            $GLOBALS['test']['child_condition_called'] = false;

            $this->createRoutes(function () {

                $this->router
                    ->where(function () {

                        $GLOBALS['test']['parent_condition_called'] = true;

                        $this->assertFalse($GLOBALS['test']['child_condition_called']);

                        return true;

                    })
                    ->group(function () {

                        $this->router
                            ->get('/bar')
                            ->where('true')
                            ->handle(function () {

                                return 'bar';

                            });

                        $this->router->where(function () {

                            $GLOBALS['test']['child_condition_called'] = true;

                            return false;

                        })->group(function () {

                            $this->router
                                ->get('/foo')
                                ->where('true')
                                ->handle(function () {

                                    $this->fail('This route should not have been called');

                                });

                        });


                    });

            });

            // When
            $get = $this->webRequest('GET', '/foo');

            // Then
            $this->runAndAssertEmptyOutput($get);
            $this->assertSame(true, $GLOBALS['test']['parent_condition_called']);
            $this->assertSame(true, $GLOBALS['test']['child_condition_called']);

            // Given
            $GLOBALS['test']['parent_condition_called'] = false;
            $GLOBALS['test']['child_condition_called'] = false;

            // When
            $get = $this->webRequest('GET', '/bar');

            // Then
            $this->runAndAssertOutput('bar', $get);
            $this->assertSame(true, $GLOBALS['test']['parent_condition_called']);
            $this->assertSame(false, $GLOBALS['test']['child_condition_called']);


        }

        /** @test */
        public function the_first_matching_route_aborts_the_iteration_over_all_current_routes()
        {

            $GLOBALS['test']['first_route_condition'] = false;

            $this->createRoutes(function () {

                $this->router->prefix('foo')->group(function () {

                    $this->router
                        ->get('/bar')
                        ->where(function () {

                            $GLOBALS['test']['first_route_condition'] = true;

                            return true;

                        })
                        ->handle(function () {

                            return 'bar1';

                        });

                    $this->router
                        ->get('/{bar}')
                        ->where(function () {

                            $this->fail('Route condition evaluated even tho we already had a matching route');

                        })
                        ->handle(function () {

                            return 'bar2';

                        });


                });


            });

            $this->runAndAssertOutput('bar1', $this->webRequest('GET', '/foo/bar'));

            $this->assertTrue($GLOBALS['test']['first_route_condition']);

        }

        /** @test */
        public function url_conditions_are_passed_even_if_one_group_in_the_chain_does_not_specify_an_url_condition()
        {


            $this->createRoutes(function () {

                $this->router->prefix('foo')->group(function () {

                    $this->router->where('true')->group(function () {

                        $this->router->get('bar', function () {

                            return 'foobar';

                        });

                    });

                });

            });

            $this->runAndAssertOutput('foobar', $this->webRequest('GET', '/foo/bar'));

            $this->runAndAssertEmptyOutput($this->webRequest('GET', '/foo'));


        }

        /** @test */
        public function url_conditions_are_passed_even_if_the_root_group_doesnt_specify_an_url_condition()
        {

            $this->createRoutes(function () {

                $this->router->where('true')->group(function () {

                    $this->router->prefix('foo')->group(function () {

                        $this->router->get('bar', function () {

                            return 'foobar';

                        });

                    });

                });


            });

            $this->runAndAssertOutput('foobar', $this->webRequest('GET', '/foo/bar'));

            $this->runAndAssertEmptyOutput($this->webRequest('GET', '/foo'));


        }

    }

