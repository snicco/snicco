<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Mockery;
    use Tests\IntegrationTest;
    use Tests\fixtures\Conditions\TrueCondition;
    use Tests\stubs\TestApp;
    use Tests\stubs\TestRequest;
    use Tests\helpers\CreateDefaultWpApiMocks;
    use Tests\helpers\CreatesWpUrls;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Contracts\ServiceProvider;
    use WPEmerge\Events\IncomingAdminRequest;
    use WPEmerge\Events\IncomingAjaxRequest;
    use WPEmerge\Events\IncomingRequest;
    use WPEmerge\Facade\WP;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Routing\CachedRouteCollection;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;

    class RoutingServiceProviderTest extends IntegrationTest
    {

        use CreatesWpUrls;


        protected function tearDown() : void
        {

            parent::tearDown();

            if( is_file($file = TESTS_DIR. DS . '_data'. DS . '__generated_route_collection') ) {

                $this->unlink($file);

            }

        }

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

            $this->expectExceptionMessage('No valid cache dir provided:');

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
                    'cache_dir' => TESTS_DIR. DS . '_data'. DS
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
        public function by_default_a_normal_uncached_route_collection_is_used () {

            $this->newTestApp();

            $this->assertInstanceOf(RouteCollection::class, TestApp::resolve(AbstractRouteCollection::class));

        }

        /** @test */
        public function a_cached_route_collection_can_be_used () {

            $this->newTestApp([
                'routing' => [
                    'cache' => true,
                    'cache_dir' => TESTS_DIR. DS. '_data'. DS
                ]
            ]);

            $routes = TestApp::resolve(AbstractRouteCollection::class);

            $this->assertInstanceOf(CachedRouteCollection::class, $routes);

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
                    'definitions' => ROUTES_DIR
                ]
            ]);

            $this->seeKernelOutput('foo', TestRequest::from('GET', '/foo'));

        }

        /** @test */
        public function the_fallback_route_controller_is_registered_for_web_routes () {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ]
            ]);

            $this->seeKernelOutput('get_fallback', TestRequest::from('GET', 'post1'));



        }

        /** @test */
        public function ajax_routes_are_loaded_for_ajax_request()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);


            $request = $this->ajaxRequest('foo_action');

            $this->seeKernelOutput('FOO_ACTION', new IncomingAjaxRequest($request));

            Mockery::close();

        }

        /** @test */
        public function admin_routes_are_loaded_for_admin_requests_and_have_the_correct_prefix_applied()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);

            $request = $this->adminRequestTo('foo');

            $this->seeKernelOutput('FOO', new IncomingAdminRequest($request));

            Mockery::close();


        }

        /** @test */
        public function named_groups_are_applied_for_admin_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'providers' => [
                    SimulateAdminProvider::class
                ]
            ]);

            $this->assertSame($this->adminUrlTo('foo'), TestApp::routeUrl('admin.foo'));

            Mockery::close();
            WP::reset();

        }

        /** @test */
        public function named_groups_are_applied_for_ajax_routes()
        {

            $this->newTestApp([
                'routing' => [
                    'definitions' => ROUTES_DIR
                ],
                'providers' => [
                    SimulateAjaxProvider::class
                ]
            ]);


            $expected = $this->ajaxUrl();


            $this->assertSame($expected, TestApp::routeUrl('ajax.foo'));

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
        use CreatesWpUrls;

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
