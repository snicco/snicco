<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use InvalidArgumentException;
use Tests\Core\RoutingTestCase;
use Snicco\Core\Routing\Exception\BadRoute;
use Snicco\Core\Routing\Exception\MethodNotAllowed;
use Tests\Core\fixtures\Middleware\GlobalMiddleware;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\WebRoutingConfigurator;

class RouteAttributesTest extends RoutingTestCase
{
    
    /** @test */
    public function basic_get_routing_works()
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function non_allowed_methods_are_transformed_to_a_405_response()
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('route2', '/foo/{bar}', RoutingTestController::class);
        
        $request = $this->frontendRequest('POST', '/foo');
        try {
            $this->runKernel($request);
            $this->fail("Expected exception.");
        } catch (MethodNotAllowed $e) {
            $this->assertStringContainsString('/foo', $e->getMessage());
        }
        
        $request = $this->frontendRequest('POST', '/foo/bar');
        try {
            $this->runKernel($request);
            $this->fail("Expected exception.");
        } catch (MethodNotAllowed $e) {
            $this->assertStringContainsString('/foo/bar', $e->getMessage());
        }
    }
    
    /** @test */
    public function get_routes_match_head_requests()
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('HEAD', '/foo');
        
        $response = $this->runKernel($request);
        $response->assertOk()->assertSee('');
    }
    
    /** @test */
    public function basic_post_routing_works()
    {
        $this->routeConfigurator()->post('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function basic_put_routing_works()
    {
        $this->routeConfigurator()->put('foo', '/foo', RoutingTestController::class);
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function basic_patch_routing_works()
    {
        $this->routeConfigurator()->patch('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function basic_delete_routing_works()
    {
        $this->routeConfigurator()->delete('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function basic_options_routing_works()
    {
        $this->routeConfigurator()->options('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function a_route_can_match_all_methods()
    {
        $this->routeConfigurator()->any('foo', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function a_route_can_match_specific_methods()
    {
        $this->routeConfigurator()->match(
            ['GET', 'POST'],
            'foo',
            '/foo',
            RoutingTestController::class
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->expectException(MethodNotAllowed::class);
        $this->runKernel($request);
    }
    
    /** @test */
    public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence()
    {
        $this->routeConfigurator()->post(
            'static',
            '/foo/baz',
            [RoutingTestController::class, 'static']
        );
        
        $this->routeConfigurator()->post(
            'dynamic',
            '/foo/{dynamic}',
            [RoutingTestController::class, 'dynamic']
        );
        
        $request = $this->frontendRequest('POST', '/foo/baz');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->frontendRequest('POST', '/foo/biz');
        $this->assertResponseBody(RoutingTestController::dynamic.':biz', $request);
    }
    
    /** @test */
    public function middleware_can_be_added_after_a_route_is_created()
    {
        $this->routeConfigurator()
             ->get('foo', '/foo', RoutingTestController::class)
             ->middleware('foo');
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':foo_middleware', $request);
    }
    
    /** @test */
    public function a_route_can_have_multiple_middlewares()
    {
        $this->routeConfigurator()
             ->get('foo', '/foo', RoutingTestController::class)
             ->middleware(['foo', 'bar']);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:foo_middleware',
            $request
        );
    }
    
    /** @test */
    public function middleware_can_pass_arguments()
    {
        $this->routeConfigurator()
             ->get('foo', '/foo', RoutingTestController::class)
             ->middleware(['foo:FOO', 'bar:BAR']);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':BAR:FOO', $request);
    }
    
    /** @test */
    public function a_route_can_be_set_to_not_handle_anything_but_only_run_middleware()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->routeConfigurator()->get('foo', '/foo')
             ->middleware(GlobalMiddleware::class);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody('', $request);
        
        $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times]);
    }
    
    /** @test */
    public function a_route_with_the_same_static_url_cant_be_added_twice()
    {
        $this->expectException(BadRoute::class);
        
        $this->routeConfigurator()->get('route1', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('route2', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody('foo1', $request);
    }
    
    /** @test */
    public function a_route_with_the_same_name_cant_be_added_twice_even_if_urls_are_different()
    {
        $this->routeConfigurator()->get('route1', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('route1', '/bar', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertEmptyBody($request);
        
        $request = $this->frontendRequest('GET', '/bar');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function config_values_can_be_accessed()
    {
        $config = ['route_path' => '/foo'];
        
        $this->refreshRouter(null, null, $config);
        
        $this->routeConfigurator()->group(function (WebRoutingConfigurator $router) {
            $path = $router->configValue('route_path');
            
            $router->get('r1', $path, RoutingTestController::class);
        });
        
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', '/foo')
        );
    }
    
    /** @test */
    public function an_exception_is_thrown_when_config_values_dont_exist()
    {
        $config = ['route_path' => '/foo'];
        
        $this->refreshRouter(null, null, $config);
        
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bogus');
        
        $this->routeConfigurator()->group(function (WebRoutingConfigurator $router) {
            $path = $router->configValue('bogus');
            
            $router->get('r1', $path, RoutingTestController::class);
        });
    }
    
}


