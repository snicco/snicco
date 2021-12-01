<?php

declare(strict_types=1);

namespace Tests\Core\integration\Routing;

use Snicco\Routing\Router;
use Snicco\Routing\UrlGenerator;
use Snicco\Contracts\RouteRegistrar;
use Snicco\Contracts\RouteUrlMatcher;
use Snicco\Contracts\RouteUrlGenerator;
use Snicco\Factories\RouteConditionFactory;
use Snicco\Routing\CachedRouteFileRegistrar;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Codeception\shared\FrameworkTestCase;
use Snicco\Routing\FastRoute\FastRouteUrlMatcher;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;

use const DS;

class RoutingServiceProviderTest extends FrameworkTestCase
{
    
    private string $route_cache_dir;
    private string $route_cache_file;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->route_cache_dir = TEST_APP_BASE_PATH.'/_route_cache';
        $this->route_cache_file =
            $this->route_cache_dir.DS.'__generated:snicco_wp_route_collection';
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        if (is_file($this->route_cache_file)) {
            $this->unlink($this->route_cache_file);
        }
        
        if (is_dir($this->route_cache_dir)) {
            rmdir($this->route_cache_dir);
        }
    }
    
    /** @test */
    public function all_conditions_are_loaded()
    {
        $this->bootApp();
        
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
    }
    
    /** @test */
    public function the_app_can_be_forced_to_match_web_routes()
    {
        $this->withAddedConfig('routing.must_match_web_routes', true);
        $this->bootApp();
        
        $this->assertTrue(TestApp::config('routing.must_match_web_routes'));
    }
    
    /** @test */
    public function api_endpoints_are_bound_in_the_config()
    {
        $this->bootApp();
        $endpoints = TestApp::config('routing.api.endpoints');
        $this->assertSame(['test' => 'api-prefix/base'], $endpoints);
        
        $preset = TestApp::config('routing.presets.test');
        $this->assertSame([
            'prefix' => 'api-prefix/base',
            'name' => 'test',
            'middleware' => ['api'],
        ], $preset);
        
        $middleware = TestApp::config('middleware.groups');
        
        // middleware groups are created but are empty.
        $this->assertSame([], $middleware['api']);
    }
    
    /** @test */
    public function the_caching_setting_defaults_to_only_in_production()
    {
        $this->bootApp();
        $this->assertFalse(TestApp::config('routing.cache'));
    }
    
    /** @test */
    public function without_caching_a_fast_route_matcher_is_returned()
    {
        $this->bootApp();
        $this->assertInstanceOf(
            FastRouteUrlMatcher::class,
            TestApp::resolve(RouteUrlMatcher::class)
        );
    }
    
    /** @test */
    public function the_default_cache_dir_is_bound()
    {
        $this->bootApp();
        
        $this->assertSame(
            TEST_APP_BASE_PATH.DS.'storage'.DS.'framework'.DS.'routes',
            TestApp::config('routing.cache_dir')
        );
    }
    
    /** @test */
    public function the_router_is_loaded_correctly()
    {
        $this->bootApp();
        $this->assertInstanceOf(Router::class, TestApp::resolve(Router::class));
    }
    
    /** @test */
    public function a_cached_route_registrar_can_be_enabled_in_the_config()
    {
        $this->withAddedConfig('routing.cache', true);
        $this->withAddedConfig('routing.cache_dir', $this->route_cache_dir);
        $this->bootApp();
        
        $registrar = TestApp::resolve(RouteRegistrar::class);
        
        $this->assertInstanceOf(CachedRouteFileRegistrar::class, $registrar);
    }
    
    /** @test */
    public function the_cache_directory_will_be_created_if_not_present()
    {
        $this->assertFalse(is_dir($this->route_cache_dir));
        $this->assertFalse(is_file($this->route_cache_file));
        
        $this->withAddedConfig('routing.cache', true);
        $this->withAddedConfig('routing.cache_dir', $this->route_cache_dir);
        $this->bootApp();
        
        $this->assertTrue(is_dir($this->route_cache_dir));
        $this->assertTrue(is_file($this->route_cache_file));
    }
    
    /** @test */
    public function the_condition_factory_can_be_loaded()
    {
        $this->bootApp();
        
        $this->assertInstanceOf(
            RouteConditionFactory::class,
            TestApp::resolve(RouteConditionFactory::class)
        );
    }
    
    /** @test */
    public function the_default_route_registrar_is_used_by_default()
    {
        $this->bootApp();
        
        $registrar = TestApp::resolve(RouteRegistrar::class);
        
        $this->assertInstanceOf(RouteRegistrar::class, $registrar);
    }
    
    /** @test */
    public function the_url_generator_can_be_resolved()
    {
        $this->bootApp();
        
        $url_g = TestApp::resolve(UrlGenerator::class);
        
        $this->assertInstanceOf(UrlGenerator::class, $url_g);
    }
    
    /** @test */
    public function the_route_url_generator_can_be_resolved()
    {
        $this->bootApp();
        
        $route_g = TestApp::resolve(RouteUrlGenerator::class);
        
        $this->assertInstanceOf(FastRouteUrlGenerator::class, $route_g);
    }
    
    /** @test */
    public function the_internal_routes_are_included()
    {
        $this->bootApp();
        
        $routes = TestApp::config('routing.definitions');
        
        $this->assertSame(PACKAGES_DIR.DS.'core'.DS.'routes', end($routes));
    }
    
}





