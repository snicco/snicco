<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use LogicException;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\AdminRoutingConfigurator;

class AdminRoutesTest extends RoutingTestCase
{
    
    private AdminRoutingConfigurator $admin_configurator;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->admin_configurator = $this->adminRouteConfigurator();
    }
    
    /** @test */
    public function an_exception_is_thrown_if_admin_routes_are_registered_with_pending_attributes_without_a_call_to_group()
    {
        $this->expectException(LogicException::class);
        $this->admin_configurator->middleware('foo')->admin('admin.1', 'admin.php/foo');
    }
    
    /** @test */
    public function an_exception_is_thrown_if_admin_routes_are_registered_with_the_prefix_declared_explicitly()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "You should not add the prefix [/wp-admin] to the admin route [admin.1]"
        );
        
        $this->admin_configurator->admin('admin.1', '/wp-admin/admin.php/foo');
    }
    
    /** @test */
    public function an_exception_is_thrown_if_an_admin_route_is_registered_without_using_the_admin_method()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            sprintf(
                "You tried to register the route [r1] that goes to the admin dashboard without using the dedicated admin() method on an instance of [%s]",
                AdminRoutingConfigurator::class
            )
        );
        
        $this->admin_configurator->get(
            'r1',
            '/wp-admin/admin.php/foo',
            RoutingTestController::class
        );
    }
    
    /** @test */
    public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path()
    {
        $this->admin_configurator->admin('r1', 'admin.php/foo', RoutingTestController::class);
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function routes_to_different_admin_pages_dont_match()
    {
        $this->admin_configurator->admin(
            'r1',
            'options.php/foo',
            RoutingTestController::class
        );
        
        $request = $this->adminRequest('GET', 'bar');
        $this->assertResponseBody('', $request);
    }
    
    /** @test */
    public function non_get_requests_do_not_match()
    {
        $router = $this->admin_configurator;
        $router->admin('r1', 'admin.php/foo', RoutingTestController::class);
        
        $request = $this->adminRequest('POST', 'foo');
        
        $response = $this->runKernel($request);
        $response->assertDelegated();
    }
    
    /** @test */
    public function two_different_admin_routes_can_be_created()
    {
        $this->admin_configurator->admin('r1', 'admin.php/foo', RoutingTestController::class);
        $this->admin_configurator->admin('r2', 'admin.php/bar', RoutingTestController::class);
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->adminRequest('GET', 'bar');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->adminRequest('GET', 'baz');
        $this->assertResponseBody('', $request);
    }
    
    /** @test */
    public function reverse_routing_works_with_admin_routes()
    {
        $this->admin_configurator->admin('r1', 'admin.php/foo', RoutingTestController::class);
        
        $url = $this->generator->toRoute('r1', ['bar' => 'baz']);
        $this->assertSame('/wp-admin/admin.php?bar=baz&page=foo', $url);
    }
    
    /** @test */
    public function a_route_with_the_same_page_query_var_but_different_parent_menu_doesnt_match()
    {
        $this->admin_configurator->admin(
            'r1',
            '/admin.php/foo',
            RoutingTestController::class
        );
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->adminRequest('GET', 'foo', 'tools.php');
        $this->assertResponseBody('', $request);
    }
    
    /** @test */
    public function admin_routes_do_not_match_for_non_admin_requests_that_have_the_same_rewritten_url_but_are_not_loaded_from_withing_the_admin_dashboard()
    {
        $this->admin_configurator->admin(
            'r1',
            'options.php/foo',
            RoutingTestController::class
        );
        
        $request = $this->frontendRequest('GET', '/wp-admin/admin.php/foo');
        $this->runKernel($request)->assertDelegated();
        
        $request = $this->adminRequest('GET', 'foo', 'options.php');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function the_real_request_path_is_available_in_the_controller_not_the_rewritten_one()
    {
        $this->admin_configurator->admin(
            'r1',
            'admin.php/foo',
            [RoutingTestController::class, 'returnFullRequest']
        );
        
        $request = $this->adminRequest('GET', 'foo');
        $as_string = (string) $request->getUri();
        $this->assertStringContainsString('page=foo', $as_string);
        $this->assertResponseBody($as_string, $request);
    }
    
    /** @test */
    public function menu_items_can_be_added()
    {
    }
    
}