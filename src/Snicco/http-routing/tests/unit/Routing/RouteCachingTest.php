<?php

declare(strict_types=1);

namespace Tests\HttpRouting\unit\Routing;

use Snicco\Core\Utils\PHPCacheFile;
use Tests\HttpRouting\RoutingTestCase;
use Tests\HttpRouting\fixtures\Conditions\MaybeRouteCondition;
use Tests\HttpRouting\fixtures\Controller\RoutingTestController;

class RouteCachingTest extends RoutingTestCase
{
    
    private PHPCacheFile $route_cache_file;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->route_cache_file = new PHPCacheFile(__DIR__, '__generated_snicco_wp_routes.php');
        
        $this->assertFalse($this->route_cache_file->isCreated());
        
        $this->refreshRouter($this->route_cache_file);
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        if (is_file($this->route_cache_file->realpath())) {
            unlink($this->route_cache_file->realpath());
        }
    }
    
    /** @test */
    public function a_route_can_be_run_when_no_cache_files_exist_yet()
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', 'foo')
        );
    }
    
    /** @test */
    public function a_cache_file_is_created_after_the_routes_are_loaded_for_the_first_time()
    {
        $this->assertFalse($this->route_cache_file->isCreated());
        
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', 'foo')
        );
        
        $this->assertTrue($this->route_cache_file->isCreated());
    }
    
    /** @test */
    public function routes_can_be_read_from_the_cache_and_match_without_needing_to_define_them()
    {
        $this->assertFalse($this->route_cache_file->isCreated());
        
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('bar', '/bar', RoutingTestController::class);
        $this->routeConfigurator()->get('baz', '/baz', RoutingTestController::class);
        $this->routeConfigurator()->get('biz', '/biz', RoutingTestController::class);
        $this->routeConfigurator()->get('boom', '/boom', RoutingTestController::class)->condition(
            MaybeRouteCondition::class,
            true
        );
        $this->routeConfigurator()->get('bang', '/bang', RoutingTestController::class)->condition(
            MaybeRouteCondition::class,
            false
        );
        
        // Creates the cache file
        $this->runKernel($this->frontendRequest('GET', 'whatever'));
        
        $this->assertTrue($this->route_cache_file->isCreated());
        
        $this->refreshRouter($this->route_cache_file);
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('GET', 'bar');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('GET', 'biz');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('GET', 'baz');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('GET', 'boom');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('GET', 'bang');
        $this->assertResponseBody('', $request);
    }
    
    /** @test */
    public function reverse_routing_works_with_cached_router()
    {
        $this->assertFalse($this->route_cache_file->isCreated());
        
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('bar', '/bar', RoutingTestController::class);
        
        $this->assertSame('/foo', $this->generator->toRoute('foo'));
        $this->assertSame('/bar', $this->generator->toRoute('bar'));
        
        // Creates the cache file
        $this->runKernel($this->frontendRequest('GET', 'whatever'));
        
        $this->assertTrue($this->route_cache_file->isCreated());
        
        $this->refreshRouter($this->route_cache_file);
        
        $this->assertSame('/foo', $this->generator->toRoute('foo'));
        $this->assertSame('/bar', $this->generator->toRoute('bar'));
    }
    
    /** @test */
    public function an_exception_is_thrown_if_a_route_is_added_to_the_cached_router()
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('bar', '/bar', RoutingTestController::class);
        
        $this->assertSame('/foo', $this->generator->toRoute('foo'));
        $this->assertSame('/bar', $this->generator->toRoute('bar'));
        
        // Creates the cache file
        $this->runKernel($this->frontendRequest('GET', 'whatever'));
        
        $this->assertTrue($this->route_cache_file->isCreated());
        
        $this->refreshRouter($this->route_cache_file);
        
        $this->expectExceptionMessage(
            "The route [route1] cant be added because the Router is already cached."
        );
        $this->routeConfigurator()->get('route1', '/foo');
    }
    
}

class Controller
{
    
    public function handle()
    {
        return 'foo';
    }
    
}