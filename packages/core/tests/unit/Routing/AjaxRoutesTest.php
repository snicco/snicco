<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;

class AjaxRoutesTest extends RoutingTestCase
{
    
    /** @test */
    public function ajax_routes_can_be_matched_by_passing_the_action_as_the_route_parameter()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->group(function () {
                $this->router->post('foo_action')->handle(function () {
                    return 'FOO_ACTION';
                });
            });
        });
        
        $ajax_request = $this->adminAjaxRequest('POST', 'foo_action');
        $this->assertResponse('FOO_ACTION', $ajax_request);
    }
    
    /** @test */
    public function a_trailing_suffix_for_admin_routes_is_stripped()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->group(function () {
                $this->router->post('/foo_action/')->handle(function () {
                    return 'FOO_ACTION';
                });
            });
        });
        
        $ajax_request = $this->adminAjaxRequest('POST', 'foo_action');
        $this->assertResponse('FOO_ACTION', $ajax_request);
    }
    
    /** @test */
    public function ajax_routes_with_the_wrong_action_dont_match()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->group(function () {
                $this->router->post('foo_action')->handle(function () {
                    return 'FOO_ACTION';
                });
            });
        });
        
        $ajax_request = $this->adminAjaxRequest('POST', 'bar_action');
        $this->assertEmptyResponse($ajax_request);
    }
    
    /** @test */
    public function ajax_routes_can_be_matched_if_the_action_parameter_is_in_the_query()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->group(function () {
                $this->router->get('foo_action')->handle(function () {
                    return 'FOO_ACTION';
                });
            });
        });
        
        $ajax_request = $this->adminAjaxRequest('GET', 'foo_action');
        
        $this->assertResponse('FOO_ACTION', $ajax_request);
        
        $this->assertEmptyResponse($ajax_request->withQueryParams(['action' => 'bar_action']));
    }
    
    /** @test */
    public function reversed_ajax_routes_for_routes_that_dont_accept_get_requests_return_the_base_url()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->name('ajax')->group(function () {
                $this->router->post('foo_action')->handle(function () {
                    //
                    
                })->name('foo');
            });
        });
        
        $expected = '/wp-admin/admin-ajax.php';
        
        $this->assertSame($expected, $this->newUrlGenerator()->toRoute('ajax.foo'));
    }
    
    /** @test */
    public function ajax_routes_can_be_reversed_for_get_request_with_the_action_in_the_query_string()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->name('ajax')
                         ->group(function () {
                             $this->router->get('foo_action', function () {
                                 //
                             })->name('foo');
                         });
        });
        
        $expected = '/wp-admin/admin-ajax.php?action=foo_action&bar=baz';
        
        $this->assertSame(
            $expected,
            $this->newUrlGenerator()
                 ->toRoute('ajax.foo', ['bar' => 'baz'])
        );
    }
    
}