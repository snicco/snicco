<?php


    declare(strict_types = 1);


    namespace Tests\unit\ServiceProviders;

    use Tests\AssertsResponse;
    use Tests\RequestTesting;
    use Tests\stubs\Conditions\TrueCondition;
    use Tests\stubs\TestApp;
    use Tests\Test;
    use Tests\TestRequest;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\Router;
    use WPEmerge\ServiceProviders\AliasServiceProvider;
    use WPEmerge\ServiceProviders\ExceptionServiceProvider;
    use WPEmerge\ServiceProviders\FactoryServiceProvider;
    use WPEmerge\ServiceProviders\HttpServiceProvider;
    use WPEmerge\ServiceProviders\RoutingServiceProvider;
    use WPEmerge\ServiceProviders\ViewServiceProvider;

    class RoutingServiceProviderTest extends Test
    {

        use BootServiceProviders;
        use RequestTesting;
        use AssertsResponse;

        function neededProviders() : array
        {

            return [
                RoutingServiceProvider::class,
                FactoryServiceProvider::class,
                AliasServiceProvider::class,
                HttpServiceProvider::class,
                ExceptionServiceProvider::class,
                ViewServiceProvider::class,
            ];

        }

        /** @test */
        public function all_conditions_are_loaded()
        {

            $this->config->set('routing.conditions.true', TrueCondition::class);

            $conditions = $this->config->get('routing.conditions');

            // user provided
            $this->assertArrayHasKey('true', $conditions);

            // core
            $this->assertArrayHasKey('custom', $conditions);
            $this->assertArrayHasKey('negate', $conditions);
            $this->assertArrayHasKey('post_id', $conditions);
            $this->assertArrayHasKey('post_slug', $conditions);
            $this->assertArrayHasKey('post_status', $conditions);
            $this->assertArrayHasKey('post_template', $conditions);
            $this->assertArrayHasKey('post_type', $conditions);
            $this->assertArrayHasKey('ajax', $conditions);
            $this->assertArrayHasKey('admin', $conditions);
            $this->assertArrayHasKey('query_string', $conditions);
            $this->assertArrayHasKey('request', $conditions);
            $this->assertArrayHasKey('admin_page', $conditions);
            $this->assertArrayHasKey('admin_ajax', $conditions);


        }

        /** @test */
        public function without_caching_a_fast_route_matcher_is_returned()
        {

            $this->assertInstanceOf(FastRouteMatcher::class, TestApp::resolve(RouteMatcher::class));

        }

        /** @test */
        public function an_exception_gets_thrown_if_a_cache_file_path_is_missing()
        {

            $this->expectExceptionMessage('No cache file provided:');

            $this->config->set('routing.cache', true);

            $this->app->resolve(RouteMatcher::class);


        }

        /** @test */
        public function a_cached_route_matcher_can_be_configured()
        {

            $this->config->set('routing.cache', true);
            $this->config->set('routing.cache_file', TESTS_DIR.'_data'.DS.'tests.route.cache.php');

            $matcher = $this->app->resolve(RouteMatcher::class);

            $this->assertInstanceOf(CachedFastRouteMatcher::class, $matcher);

        }

        /** @test */
        public function the_router_is_loaded_correctly()
        {

            $this->assertInstanceOf(Router::class, $this->app->resolve(Router::class));

        }

        /** @test */
        public function the_condition_factory_can_be_loaded()
        {


            $this->assertInstanceOf(ConditionFactory::class, $this->app->resolve(ConditionFactory::class));

        }

        /** @test */
        public function ajax_routes_are_loaded_for_ajax_request()
        {

            $this->config->set('routing.definitions', TESTS_DIR.DS.'stubs'.DS.'Routes');

            WP::shouldReceive('isAdminAjax')->andReturnTrue();
            WP::shouldReceive('isUserLoggedIn')->andReturnTrue();

            $this->boostrapProviders();

            $router = $this->resolveRouter();

            $request = $this->ajaxRequest('foo_action');

            $response = $router->runRoute($request);

            $this->assertOutput('FOO_ACTION', $response);

        }

        /** @test */
        public function admin_routes_are_loaded_for_admin_requests_and_have_the_correct_prefix_applied()
        {

            $this->config->set('routing.definitions', TESTS_DIR.DS.'stubs'.DS.'Routes');
            WP::shouldReceive('isAdminAjax')->andReturnFalse();
            WP::shouldReceive('isAdmin')->andReturnTrue();
            $this->boostrapProviders();

            $router = $this->resolveRouter();
            $response = $router->runRoute($this->adminRequestTo('foo'));

            $this->assertOutput('FOO', $response);

        }

        /** @test */
        public function web_routes_are_loaded_by_default()
        {

            $this->config->set('routing.definitions', TESTS_DIR.DS.'stubs'.DS.'Routes');
            $this->boostrapProviders();

            /** @var Router $router */
            $router = $this->app->resolve(Router::class);

            $router->middlewareGroup('web', []);

            $request = TestRequest::from('GET', 'foo');

            $response = $router->runRoute($request);

            $this->assertOutput('foo', $response);

        }

        /** @test */
        public function named_groups_are_applied_for_admin_routes()
        {
            $this->config->set('routing.definitions', TESTS_DIR.DS.'stubs'.DS.'Routes');
            WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {

                return $this->adminUrlTo($page);

            });
            WP::shouldReceive('isAdminAjax')->andReturnFalse();
            WP::shouldReceive('isAdmin')->andReturnTrue();
            $this->boostrapProviders();

            $router = $this->resolveRouter();

            $url = $router->getRouteUrl('admin.foo');

            $this->assertSame($this->adminUrlTo('foo'), $url);

        }

        /** @test */
        public function named_groups_are_applied_for_ajax_routes()
        {
            $this->config->set('routing.definitions', TESTS_DIR.DS.'stubs'.DS.'Routes');
            WP::shouldReceive('isAdminAjax')->andReturnTrue();
            WP::shouldReceive('isAdmin')->andReturnTrue();
            $this->boostrapProviders();

            $router = $this->resolveRouter();

            $expected = $this->ajaxUrl();

            $this->assertSame($expected, $router->getRouteUrl('ajax.foo'));


        }



        private function resolveRouter() : Router
        {

            /** @var Router $router */
            $router = $this->app->resolve(Router::class);

            $router->middlewareGroup('ajax', []);
            $router->middlewareGroup('admin', []);
            $router->middlewareGroup('web', []);

            return $router;
        }


    }
