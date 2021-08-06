<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Snicco\Contracts\AbstractRouteCollection;
    use Snicco\Contracts\RouteMatcher;
    use Snicco\Contracts\RouteRegistrarInterface;
    use Snicco\Contracts\RouteUrlGenerator;
    use Snicco\Factories\ConditionFactory;
    use Snicco\Routing\CachedRouteCollection;
    use Snicco\Routing\CacheFileRouteRegistrar;
    use Snicco\Routing\FastRoute\CachedFastRouteMatcher;
    use Snicco\Routing\FastRoute\FastRouteMatcher;
    use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
    use Snicco\Routing\RouteCollection;
    use Snicco\Routing\Router;
    use Snicco\Routing\RouteRegistrar;
    use Snicco\Routing\UrlGenerator;
    use Tests\stubs\TestApp;
    use Tests\TestCase;

    class RoutingServiceProviderTest extends TestCase
    {

        protected bool $defer_boot = true;

        protected function tearDown() : void
        {

            parent::tearDown();

            if (is_file($file = TESTS_DIR.DS.'_data'.DS.'__generated_route_collection')) {

                $this->unlink($file);

            }

        }

        /** @test */
        public function all_conditions_are_loaded()
        {


            $this->boot();

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
        public function the_app_can_be_forced_to_match_web_routes()
        {

            $this->withAddedConfig('routing.must_match_web_routes', true);

            $this->boot();

            $this->assertTrue(TestApp::config('routing.must_match_web_routes'));

        }

        /** @test */
        public function api_endpoints_are_bound_in_the_config()
        {

            $this->boot();
            $endpoints = TestApp::config('routing.api.endpoints');
            $this->assertSame(['test' => 'api-prefix/base'], $endpoints);

            $preset = TestApp::config('routing.presets.api.test');
            $this->assertSame([
                'prefix' => 'api-prefix/base',
                'name' => 'test',
                'middleware' => ['api.test']
            ], $preset);

            $middleware = TestApp::config('middleware.groups');

            $this->assertSame([], $middleware['api.test']);

        }

        /** @test */
        public function the_caching_setting_defaults_to_only_in_production () {

            $this->boot();
            $this->assertFalse(TestApp::config('routing.cache'));

        }

        /** @test */
        public function without_caching_a_fast_route_matcher_is_returned()
        {

            $this->boot();
            $this->assertInstanceOf(FastRouteMatcher::class, TestApp::resolve(RouteMatcher::class));

        }

        /** @test */
        public function the_default_cache_dir_is_bound () {


            $this->boot();

            $this->assertSame(FIXTURES_DIR.DS.'storage'.DS.'framework'.DS.'routes', TestApp::config('routing.cache_dir'));

        }

        /** @test */
        public function a_cached_route_matcher_can_be_configured()
        {

            $this->withAddedConfig('routing.cache', true);
            $this->withAddedConfig('routing.cache_dir', TESTS_DIR.DS.'_data'.DS);
            $this->boot();

            $matcher = TestApp::resolve(RouteMatcher::class);

            $this->assertInstanceOf(CachedFastRouteMatcher::class, $matcher);

        }

        /** @test */
        public function the_router_is_loaded_correctly()
        {

            $this->boot();

            $this->assertInstanceOf(Router::class, TestApp::resolve(Router::class));

        }

        /** @test */
        public function by_default_a_normal_uncached_route_collection_is_used()
        {

            $this->boot();

            $this->assertInstanceOf(RouteCollection::class, TestApp::resolve(AbstractRouteCollection::class));

        }

        /** @test */
        public function a_cached_route_collection_can_be_used()
        {

            $this->withAddedConfig('routing.cache', true);
            $this->withAddedConfig('routing.cache_dir', TESTS_DIR.DS.'_data'.DS);
            $this->boot();

            $routes = TestApp::resolve(AbstractRouteCollection::class);

            $this->assertInstanceOf(CachedRouteCollection::class, $routes);

        }

        /** @test */
        public function a_cached_route_registrar_can_be_enabled_in_the_config()
        {

            $this->withAddedConfig('routing.cache', true);
            $this->withAddedConfig('routing.cache_dir', TESTS_DIR.DS.'_data'.DS);
            $this->boot();

            $registrar = TestApp::resolve(RouteRegistrarInterface::class);

            $this->assertInstanceOf(CacheFileRouteRegistrar::class, $registrar);

        }

        /** @test */
        public function the_condition_factory_can_be_loaded()
        {

            $this->boot();

            $this->assertInstanceOf(ConditionFactory::class, TestApp::resolve(ConditionFactory::class));

        }

        /** @test */
        public function the_default_route_registrar_is_used_by_default()
        {

            $this->boot();

            $registrar = TestApp::resolve(RouteRegistrarInterface::class);

            $this->assertInstanceOf(RouteRegistrar::class, $registrar);
        }

        /** @test */
        public function the_url_generator_can_be_resolved()
        {

            $this->boot();

            $url_g = TestApp::resolve(UrlGenerator::class);

            $this->assertInstanceOf(UrlGenerator::class, $url_g);

        }

        /** @test */
        public function the_route_url_generator_can_be_resolved()
        {

            $this->boot();

            $route_g = TestApp::resolve(RouteUrlGenerator::class);

            $this->assertInstanceOf(FastRouteUrlGenerator::class, $route_g);


        }

        /** @test */
        public function the_internal_routes_are_included()
        {

            $this->boot();

            $routes = TestApp::config('routing.definitions');

            $this->assertContains(ROOT_DIR.DS.'routes', $routes);

        }

    }





