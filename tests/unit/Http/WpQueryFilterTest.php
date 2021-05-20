<?php


    declare(strict_types = 1);


    namespace Tests\unit\Http;

    use Mockery;
    use Tests\stubs\TestRequest;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\SetUpKernel;
    use Tests\UnitTest;
    use WPEmerge\Application\ApplicationEvent;
    use WPEmerge\Events\FilterWpQuery;
    use WPEmerge\Facade\WP;
    use WPEmerge\Routing\CompiledRoute;
    use WPEmerge\Routing\Route;
    use WPEmerge\Routing\RouteResult;

    class WpQueryFilterTest extends UnitTest
    {

        use SetUpKernel;
        use CreateDefaultWpApiMocks;

        protected function beforeTestRun()
        {

            $this->router = $this->newRouter($c = $this->createContainer());
            $this->kernel = $this->newKernel($this->router, $c);
            ApplicationEvent::make($c);
            ApplicationEvent::fake();
            WP::setFacadeContainer($c);

        }

        protected function beforeTearDown()
        {

            ApplicationEvent::setInstance(null);
            WP::reset();
            Mockery::close();

        }

        /** @test */
        public function the_main_wp_query_vars_can_be_filtered_when_a_route_matches()
        {

            $this->router->get('foo', function () {
                //
            })->wpquery(function (array $query_vars) {

                return [
                    'foo' => 'baz',
                ];

            });

            $this->router->loadRoutes();

            $query_vars = ['foo' => 'bar'];

            $request = TestRequest::from('GET', 'foo');

            $filtered = $this->kernel->filterRequest(new FilterWpQuery($request, $query_vars));

            $this->assertSame(['foo' => 'baz'], $filtered);


        }

        /** @test */
        public function the_route_url_params_get_passed_to_the_filter()
        {

            $this->router->get('teams/{county}/{name}', function () {
                //
            })->wpquery(function (array $query_vars, $county, $name) {

                return array_merge($query_vars, [$county => $name]);

            });

            $this->router->loadRoutes();

            $query_vars = ['spain' => 'barcelona'];

            $request = TestRequest::from('GET', 'teams/germany/dortmund');

            $filtered = $this->kernel->filterRequest(new FilterWpQuery($request, $query_vars));

            $this->assertSame(['spain' => 'barcelona', 'germany' => 'dortmund'], $filtered);


        }

        /** @test */
        public function the_route_handler_does_not_get_run_when_filtering_the_wp_query () {


            $this->router->get('foo', function () {

                $this->fail('The route was run when filtering');

            })->wpquery(function () {

                return [
                    'foo' => 'baz',
                ];

            });

            $this->router->loadRoutes();

            $query_vars = ['foo' => 'bar'];

            $request = TestRequest::from('GET', 'foo');

            $filtered = $this->kernel->filterRequest(new FilterWpQuery($request, $query_vars));

            $this->assertSame(['foo' => 'baz'], $filtered);

        }


        /** @test */
        public function the_query_vars_dont_get_changed_when_no_route_matches () {

            $this->router->get('foo', function () {

                return 'FOO';

            })->wpquery(function (array $query_vars) {

                return [
                    'foo' => 'baz',
                ];

            });

            $this->router->loadRoutes();

            $query_vars = ['foo' => 'bar'];
            $request = TestRequest::from('POST', 'bar');

            $filtered = $this->kernel->filterRequest( new FilterWpQuery( $request, $query_vars ) );

            $this->assertSame(['foo' => 'bar'], $filtered);

        }


    }