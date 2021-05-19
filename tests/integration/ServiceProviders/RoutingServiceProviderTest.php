<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Mockery;
    use Tests\IntegrationTest;
    use Tests\traits\AssertsResponse;
    use Tests\stubs\Conditions\TrueCondition;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\traits\CreateDefaultWpApiMocks;
    use Tests\traits\CreateWpTestUrls;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\UrlGenerator;
    use WPEmerge\Support\Url;

    class RoutingServiceProviderTest extends IntegrationTest
    {

        use CreateWpTestUrls;
        use AssertsResponse;


        /** @test */
        public function all_conditions_are_loaded()
        {

            $this->newTestApp([
                'routing' => [
                    'conditions' => [
                        'true' => TrueCondition::class
                    ]
                ]
            ]);


            $conditions = TestApp::config('routing.conditions');

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
            $this->assertArrayHasKey('query_string', $conditions);
            $this->assertArrayHasKey('request', $conditions);
            $this->assertArrayHasKey('admin_page', $conditions);
            $this->assertArrayHasKey('admin_ajax', $conditions);


        }

        /** @test */
        public function without_caching_a_fast_route_matcher_is_returned()
        {

            $this->newTestApp();

            $this->assertInstanceOf(FastRouteMatcher::class, TestApp::resolve(RouteMatcher::class));

        }

        /** @test */
        public function an_exception_gets_thrown_if_a_cache_file_path_is_missing()
        {

            $this->expectExceptionMessage('No cache file provided:');

            $this->newTestApp([
                'routing' => [
                    'cache' => true
                ]
            ]);


            TestApp::resolve(RouteMatcher::class);


        }

        /** @test */
        public function a_cached_route_matcher_can_be_configured()
        {

            $this->newTestApp([
                'routing' => [
                    'cache' => true,
                    'cache_file' => TESTS_DIR.'_data'.DS.'tests.route.cache.php'
                ]
            ]);

            $matcher = TestApp::resolve(RouteMatcher::class);

            $this->assertInstanceOf(CachedFastRouteMatcher::class, $matcher);

        }

        /** @test */
        public function the_router_is_loaded_correctly()
        {

            $this->newTestApp();

            $this->assertInstanceOf(Router::class, TestApp::resolve(Router::class));

        }

        /** @test */
        public function the_condition_factory_can_be_loaded()
        {
            $this->newTestApp();

            $this->assertInstanceOf(ConditionFactory::class, TestApp::resolve(ConditionFactory::class));

        }

        /** @test */
        public function web_routes_are_loaded_by_default()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ]
            ]);

            /** @var Router $router */
            $router = TestApp::resolve(Router::class);

            // Needed because the sync of middleware to the router happens in the kernel.
            $router->middlewareGroup('web', []);

            $request = TestRequest::from('GET', 'foo');

            $response = $router->runRoute($request);

            $this->assertOutput('foo', $response);

            Mockery::close();

        }

        /** @test */
        public function the_fallback_route_controller_is_registered_for_web_routes () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ]
            ]);

            /** @var Router $router */
            $router = TestApp::resolve(Router::class);

            // Needed because the sync of middleware to the router happens in the kernel.
            $router->middlewareGroup('web', []);

            $request = TestRequest::from('GET', 'whatever');

            $response = $router->runRoute($request);

            $this->assertOutput('FOO', $response);

        }

        /** @test */
        public function ajax_routes_are_loaded_for_ajax_request()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);


            /** @var Router $router */
            $router = TestApp::resolve(Router::class);
            $router->middlewareGroup('ajax', []);

            $request = $this->ajaxRequest('foo_action');

            $response = $router->runRoute($request);

            $this->assertOutput('FOO_ACTION', $response);

            Mockery::close();

        }

        /** @test */
        public function admin_routes_are_loaded_for_admin_requests_and_have_the_correct_prefix_applied()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);


            /** @var Router $router */
            $router = TestApp::resolve(Router::class);
            $router->middlewareGroup('admin', []);

            $response = $router->runRoute($this->adminRequestTo('foo'));

            $this->assertOutput('FOO', $response);

            Mockery::close();

        }


        /** @test */
        public function named_groups_are_applied_for_admin_routes()
        {
            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);

            /** @var Router $router */
            $router = TestApp::resolve(Router::class);
            $router->middlewareGroup('admin', []);

            /**
             * @var UrlGenerator $url_generator
             */
            $url_generator = TestApp::resolve(UrlGenerator::class);

            $this->assertSame($this->adminUrlTo('foo'), $url_generator->toRoute('admin.foo'));

            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function named_groups_are_applied_for_ajax_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => TESTS_DIR.DS.'stubs'.DS.'Routes'
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);

            /** @var Router $router */
            $router = TestApp::resolve(Router::class);
            $router->middlewareGroup('ajax', []);


            $expected = $this->ajaxUrl();

            /**
             * @var UrlGenerator $url_generator
             */
            $url_generator = TestApp::resolve(UrlGenerator::class);

            $this->assertSame($expected, $url_generator->toRoute('ajax.foo'));

            Mockery::close();
            WP::reset();

        }


    }

    class SimulateAjaxProvider extends ServiceProvider
    {

        use CreateDefaultWpApiMocks;

        public function register() : void
        {
            $this->createDefaultWpApiMocks();
            WP::shouldReceive('isAdminAjax')->andReturnTrue();
            WP::shouldReceive('isUserLoggedIn')->andReturnTrue();
        }

        function bootstrap() : void
        {

        }

    }

    class SimulateAdminProvider extends ServiceProvider
    {
        use CreateDefaultWpApiMocks;
        use CreateWpTestUrls;

        public function register() : void
        {
            $this->createDefaultWpApiMocks();

            WP::shouldReceive('isAdminAjax')->andReturnFalse();
            WP::shouldReceive('isAdmin')->andReturnTrue();
            WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {

                return $this->adminUrlTo($page);

            });
        }

        function bootstrap() : void
        {

        }

    }
