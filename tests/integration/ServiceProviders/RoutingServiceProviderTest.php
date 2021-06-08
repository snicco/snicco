<?php


    declare(strict_types = 1);


    namespace Tests\integration\ServiceProviders;

    use Tests\IntegrationTest;
    use Tests\fixtures\Conditions\TrueCondition;
    use Tests\stubs\TestApp;
    use WPEmerge\Contracts\AbstractRouteCollection;
    use WPEmerge\Contracts\RouteMatcher;
    use WPEmerge\Contracts\RouteRegistrarInterface;
    use WPEmerge\Contracts\RouteUrlGenerator;
    use WPEmerge\Factories\ConditionFactory;
    use WPEmerge\Routing\CachedRouteCollection;
    use WPEmerge\Routing\CacheFileRouteRegistrar;
    use WPEmerge\Routing\FastRoute\CachedFastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteMatcher;
    use WPEmerge\Routing\FastRoute\FastRouteUrlGenerator;
    use WPEmerge\Routing\RouteCollection;
    use WPEmerge\Routing\Router;
    use WPEmerge\Routing\RouteRegistrar;
    use WPEmerge\Routing\UrlGenerator;

    class RoutingServiceProviderTest extends IntegrationTest
    {


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

            $this->newTestApp([
                'routing' => [
                    'conditions' => [
                        'true' => TrueCondition::class,
                    ],
                ],
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
        public function a_cached_route_matcher_can_be_configured()
        {

            $this->newTestApp([
                'routing' => [
                    'cache' => true,
                    'cache_dir' => TESTS_DIR.DS.'_data'.DS,
                ],
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
        public function by_default_a_normal_uncached_route_collection_is_used()
        {

            $this->newTestApp();

            $this->assertInstanceOf(RouteCollection::class, TestApp::resolve(AbstractRouteCollection::class));

        }

        /** @test */
        public function a_cached_route_collection_can_be_used()
        {

            $this->newTestApp([
                'routing' => [
                    'cache' => true,
                    'cache_dir' => TESTS_DIR.DS.'_data'.DS,
                ],
            ]);

            $routes = TestApp::resolve(AbstractRouteCollection::class);

            $this->assertInstanceOf(CachedRouteCollection::class, $routes);

        }

        /** @test */
        public function a_cached_route_registrar_can_be_enabled_in_the_config () {

            $this->newTestApp([
                'routing' => [
                    'cache' => true,
                    'cache_dir' => TESTS_DIR.DS.'_data'.DS,
                ],
            ]);

            $registrar = TestApp::resolve(RouteRegistrarInterface::class);

            $this->assertInstanceOf(CacheFileRouteRegistrar::class, $registrar);

        }

        /** @test */
        public function the_condition_factory_can_be_loaded()
        {

            $this->newTestApp();

            $this->assertInstanceOf(ConditionFactory::class, TestApp::resolve(ConditionFactory::class));

        }

        /** @test */
        public function the_default_route_registrar_is_used_by_default () {

            $this->newTestApp();

            $registrar = TestApp::resolve(RouteRegistrarInterface::class);

            $this->assertInstanceOf(RouteRegistrar::class, $registrar);
        }

        /** @test */
        public function the_url_generator_can_be_resolved () {

            $this->newTestApp();

            $url_g = TestApp::resolve(UrlGenerator::class);

            $this->assertInstanceOf(UrlGenerator::class, $url_g);

        }

        /** @test */
        public function the_route_url_generator_can_be_resolved () {

            $this->newTestApp();

            $route_g = TestApp::resolve(RouteUrlGenerator::class);

            $this->assertInstanceOf(FastRouteUrlGenerator::class, $route_g);


        }


    }





