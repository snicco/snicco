<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use LogicException;
use Tests\Core\RoutingTestCase;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Routing\RoutingConfigurator;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

class AdminRoutesTest extends RoutingTestCase
{
    
    /** @test */
    public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path()
    {
        $this->routeConfigurator()->admin('r1', 'admin.php/foo', RoutingTestController::class);
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function routes_to_different_admin_pages_dont_match()
    {
        $this->routeConfigurator()->admin('r1', 'options.php/foo', RoutingTestController::class);
        
        $request = $this->adminRequest('GET', 'bar');
        $this->assertResponseBody('', $request);
    }
    
    /** @test */
    public function no_prefixes_can_be_used_with_admin_routes()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage("Its not possible to add a prefix to admin route [r1].");
        
        $this->routeConfigurator()->prefix('foo')->group(function (RoutingConfigurator $router) {
            $router->admin('r1', 'admin/foo', RoutingTestController::class);
        });
    }
    
    /** @test */
    public function two_different_admin_routes_can_be_created()
    {
        $this->routeConfigurator()->admin('r1', 'admin.php/foo', RoutingTestController::class);
        $this->routeConfigurator()->admin('r2', 'admin.php/bar', RoutingTestController::class);
        
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
        $this->routeConfigurator()->admin('r1', 'admin.php/foo', RoutingTestController::class);
        
        $url = $this->generator->toRoute('r1', ['bar' => 'baz']);
        $this->assertSame('/wp-admin/admin.php?bar=baz&page=foo', $url);
    }
    
    /** @test */
    public function a_route_with_the_same_page_query_var_but_different_parent_menu_doesnt_match()
    {
        $this->routeConfigurator()->admin('r1', '/admin.php/foo', RoutingTestController::class);
        
        $request = $this->adminRequest('GET', 'foo', 'admin.php');
        $this->assertResponseBody(RoutingTestController::static, $request);
        
        $request = $this->adminRequest('GET', 'foo', 'tools.php');
        $this->assertResponseBody('', $request);
    }
    
    /** @test */
    public function admin_routes_do_not_match_for_non_admin_requests_that_have_the_same_rewritten_url_but_are_not_loaded_from_withing_the_admin_dashboard()
    {
        $this->routeConfigurator()->admin('r1', 'options.php/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/wp-admin/admin.php/foo');
        $this->runKernel($request)->assertDelegatedToWordPress();
        
        $request = $this->adminRequest('GET', 'foo', 'options.php');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
    
    /** @test */
    public function its_not_possible_to_match_admin_ajax_requests()
    {
        $this->routeConfigurator()->admin('r1', 'admin-ajax.php/foo', RoutingTestController::class);
        
        $request = $this->psrServerRequestFactory()->createServerRequest(
            'GET',
            '/wp-admin/admin-ajax.php/foo',
            ['SCRIPT_NAME' => 'wp-admin/admin-ajax.php']
        );
        $this->runKernel(new Request($request))->assertDelegatedToWordPress();
        
        $request = $this->psrServerRequestFactory()->createServerRequest(
            'GET',
            '/wp-admin/admin-ajax.php?page=foo',
            ['SCRIPT_NAME' => 'wp-admin/admin-ajax.php']
        );
        $this->runKernel(new Request($request))->assertDelegatedToWordPress();
    }
    
}