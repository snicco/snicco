<?php


    declare(strict_types = 1);


    namespace Tests\unit\Routing;

    use Mockery;
    use Tests\UnitTest;
    use Tests\traits\CreateWpTestUrls;
    use Tests\traits\SetUpRouter;
    use WPEmerge\ExceptionHandling\Exceptions\RouteLogicException;
    use WPEmerge\Facade\WP;

    class AjaxRoutesTest extends UnitTest
    {

        use SetUpRouter;
        use CreateWpTestUrls;

        protected function beforeTestRun()
        {

            $this->newRouter($c = $this->createContainer());
            WP::setFacadeContainer($c);
            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('isAdminAjax')->andReturnTrue();


        }

        protected function beforeTearDown()
        {

            WP::setFacadeContainer(null);
            WP::clearResolvedInstances();
            Mockery::close();
        }


        /** @test */
        public function ajax_routes_can_be_matched_by_passing_the_action_as_the_route_parameter()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('foo_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertOutput('FOO_ACTION', $response);


        }

        /** @test */
        public function ajax_routes_with_the_wrong_action_dont_match()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('bar_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertNullResponse($response);

        }

        /** @test */
        public function ajax_routes_can_be_matched_if_the_action_parameter_is_in_the_query()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->get('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('foo_action', 'GET')
                                 ->withParsedBody([])
                                 ->withQueryParams(['action' => 'foo_action']);


            $response = $this->router->runRoute($ajax_request);
            $this->assertOutput('FOO_ACTION', $response);

        }

        /** @test */
        public function if_the_action_is_not_correct_but_the_url_is_the_route_will_not_match()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->get('foo_action')->handle(function () {

                    return 'FOO_ACTION';

                });

            });

            $ajax_request = $this->ajaxRequest('foo_action', 'GET', 'admin-ajax.php/foo_action')
                ->withParsedBody(['action' => 'bogus']);

            $response = $this->router->runRoute($ajax_request);
            $this->assertNullResponse( $response );

        }

        /** @test */
        public function the_action_is_passed_to_the_route_handler()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->post('bar_action')->handle(function ($request, $action) {

                    return strtoupper($action);

                });

            });

            $ajax_request = $this->ajaxRequest('bar_action');

            $response = $this->router->runRoute($ajax_request);
            $this->assertOutput('BAR_ACTION', $response);

        }

        /** @test */
        public function the_action_is_passed_to_the_route_handler_for_get_requests()
        {

            $this->router->group(['prefix' => 'wp-admin/admin-ajax.php'], function () {

                $this->router->get('bar_action')->handle(function ($request, $action) {

                    return strtoupper($action);

                });

            });

            $ajax_request = $this->ajaxRequest('bar_action', 'GET')
                ->withParsedBody([])
                ->withQueryParams(['action' => 'bar_action']);

            $response = $this->router->runRoute($ajax_request);
            $this->assertOutput('BAR_ACTION', $response);

        }

        /** @test */
        public function ajax_routes_can_be_reversed()
        {

            $this->router->group([
                'prefix' => 'wp-admin/admin-ajax.php', 'name' => 'ajax',
            ], function () {

                $this->router->post('foo_action')->handle(function () {

                    //

                })->name('foo');

            });

            $expected = $this->ajaxUrl();

            $this->assertSame($expected, $this->router->getRouteUrl('ajax.foo'));

        }

        /** @test */
        public function ajax_routes_can_be_reversed_for_get_request_with_the_action_in_the_query_string()
        {

            WP::shouldReceive('addQueryArg')
              ->with('action', 'foo_action', $this->ajaxUrl())
              ->andReturn($this->ajaxUrl().'?action=foo_action');

            $this->router->group([
                'prefix' => 'wp-admin/admin-ajax.php', 'name' => 'ajax',
            ], function () {

                $this->router->get('foo_action')->handle(function () {

                    //

                })->name('foo');

            });

            $expected = $this->ajaxUrl().'?action=foo_action';

            $this->assertSame($expected, $this->router->getRouteUrl('ajax.foo', ['method' => 'GET']));

        }

        /** @test */
        public function an_exception_gets_thrown_when_the_route_doesnt_support_get_requests()
        {

            $this->expectException('Route: ajax.foo does not respond to GET requests.');
            $this->expectException(RouteLogicException::class);

            $this->router->group([
                'prefix' => 'wp-admin/admin-ajax.php', 'name' => 'ajax',
            ], function () {

                $this->router->post('foo_action')->handle(function () {

                    //

                })->name('foo');

            });

            $expected = $this->ajaxUrl().'?action=foo_action';

            $this->assertSame($expected, $this->router->getRouteUrl('ajax.foo', ['method' => 'GET']));

        }

    }