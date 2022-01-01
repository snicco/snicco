<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use RuntimeException;
use Tests\Core\RoutingTestCase;
use Snicco\Core\Routing\RoutingConfigurator;
use Snicco\Core\ExceptionHandling\Exceptions\RouteNotFound;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

class RouteGroupsTest extends RoutingTestCase
{
    
    /** @test */
    public function an_exception_is_thrown_if_a_route_is_added_and_delegated_attributes_have_not_been_applied()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Delegated attributes have not');
        
        $this->routeConfigurator()->prefix('foo')->get('r1', '/bar', RoutingTestController::class);
    }
    
    /** @test */
    public function middleware_is_merged_for_route_groups()
    {
        $this->routeConfigurator()
             ->middleware('foo:FOO')
             ->group(function (RoutingConfigurator $router) {
                 $router
                     ->get('r1', '/foo', RoutingTestController::class)
                     ->middleware('bar:BAR');
            
                 $router
                     ->post('r2', '/foo', RoutingTestController::class);
             });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':BAR:FOO', $get_request);
        
        $post_request = $this->frontendRequest('POST', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':FOO', $post_request);
    }
    
    /** @test */
    public function the_group_namespace_is_applied_to_child_routes()
    {
        $this->routeConfigurator()
             ->namespace(self::CONTROLLER_NAMESPACE)
             ->group(function (RoutingConfigurator $router) {
                 $router->get('r1', '/foo', 'RoutingTestController');
             });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $get_request);
    }
    
    /** @test */
    public function a_group_can_prefix_all_child_route_urls()
    {
        $this->routeConfigurator()
             ->prefix('foo')
             ->group(function (RoutingConfigurator $router) {
                 $router->get('r1', '/bar', RoutingTestController::class);
                 $router->get('r2', '/baz', RoutingTestController::class);
             });
        
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', '/foo/bar')
        );
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', '/foo/baz')
        );
        $this->assertEmptyBody($this->frontendRequest('GET', '/foo'));
        
        $this->assertSame('/foo/bar', $this->generator->toRoute('r1'));
    }
    
    /** @test */
    public function a_group_name_can_be_prefixed_to_child_routes()
    {
        $this->routeConfigurator()
             ->name('users')
             ->group(function (RoutingConfigurator $router) {
                 $router->get('route1', '/bar', RoutingTestController::class);
                 $router->get('route2', '/baz', RoutingTestController::class);
             });
        
        $this->assertSame('/bar', $this->generator->toRoute('users.route1'));
        $this->assertSame('/baz', $this->generator->toRoute('users.route2'));
        
        $this->expectException(RouteNotFound::class);
        $this->generator->toRoute('route1');
    }
    
    /** @test */
    public function the_namespace_is_always_overwritten_by_child_routes()
    {
        $this->routeConfigurator()
             ->namespace('Tests\FalseNamespace')
             ->group(function (RoutingConfigurator $router) {
                 $router
                     ->namespace(self::CONTROLLER_NAMESPACE)->group(
                         function (RoutingConfigurator $router) {
                             $router->get('r1', '/foo', 'RoutingTestController');
                         }
                     );
             });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static, $get_request);
    }
    
    /** @test */
    public function group_prefixes_are_merged_on_multiple_levels()
    {
        $this->routeConfigurator()->prefix('foo')->group(function (RoutingConfigurator $router) {
            $router
                ->prefix('bar')
                ->group(function (RoutingConfigurator $router) {
                    $router->get('r1', '/baz', RoutingTestController::class);
                    $router->get('r2', '/biz', RoutingTestController::class);
                });
        });
        
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', '/foo/bar/baz')
        );
        
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', '/foo/bar/biz')
        );
        
        $this->assertEmptyBody($this->frontendRequest('GET', '/baz'));
        $this->assertEmptyBody($this->frontendRequest('GET', '/biz'));
        $this->assertEmptyBody($this->frontendRequest('GET', '/bar/baz'));
        $this->assertEmptyBody($this->frontendRequest('GET', '/bar/biz'));
    }
    
    /** @test */
    public function group_names_are_merged_on_multiple_levels()
    {
        $this->routeConfigurator()
             ->name('users')
             ->group(function (RoutingConfigurator $router) {
                 $router->name('admins')->group(function (RoutingConfigurator $router) {
                     $router->get('calvin', '/bar', RoutingTestController::class);
                     $router->get('marlon', '/baz', RoutingTestController::class);
                 });
            
                 $router->get('jon', '/jon', RoutingTestController::class);
             });
        
        $this->assertSame('/bar', $this->generator->toRoute('users.admins.calvin'));
        $this->assertSame('/baz', $this->generator->toRoute('users.admins.marlon'));
        $this->assertSame('/jon', $this->generator->toRoute('users.jon'));
        
        $this->expectException(RouteNotFound::class);
        $this->generator->toRoute('admins.calvin');
    }
    
    /** @test */
    public function middleware_is_merged_on_multiple_levels()
    {
        $this->routeConfigurator()
             ->middleware('foo:FOO')
             ->group(function (RoutingConfigurator $router) {
                 $router->middleware('bar:BAR')->group(function (RoutingConfigurator $router) {
                     $router
                         ->get('r1', '/foo', RoutingTestController::class)
                         ->middleware('baz');
                 });
             });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(
            RoutingTestController::static.':baz_middleware:BAR:FOO',
            $get_request
        );
    }
    
}

