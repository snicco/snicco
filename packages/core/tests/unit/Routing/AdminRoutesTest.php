<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Support\WP;
use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;

class AdminRoutesTest extends RoutingTestCase
{
    
    /** @test */
    public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('admin.php/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function routes_to_different_admin_pages_dont_match()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('admin.php/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'bar');
        $this->assertResponse('', $request);
    }
    
    /** @test */
    public function the_admin_preset_works_with_nested_route_groups()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->name('foo_group')->group(function () {
                    $this->router->get('admin.php/foo', function (Request $request) {
                        return $request->input('page');
                    });
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function two_different_admin_routes_can_be_created()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('admin.php/foo', function (Request $request) {
                    return $request->input('page');
                });
                
                $this->router->get('admin.php/bar', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->adminRequest('GET', 'bar');
        $this->assertResponse('bar', $request);
        
        $request = $this->adminRequest('GET', 'baz');
        $this->assertResponse('', $request);
    }
    
    /** @test */
    public function admin_routes_can_match_different_inbuilt_wp_subpages()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('users.php/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'users.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function a_route_with_the_same_page_query_var_but_different_parent_menu_doesnt_match()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('users.php/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'admin.php');
        $this->assertResponse('', $request);
    }
    
    /** @test */
    public function reverse_routing_works_with_admin_routes()
    {
        WP::shouldReceive('pluginPageUrl')->andReturnUsing(function ($page) {
            return $this->adminUrlTo($page);
        });
        
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->name('admin')->group(function () {
                $this->router->name('foo')
                             ->get('admin.php/foo', function (Request $request) {
                                 return $request->input('page');
                             });
            });
        });
        
        $url = $this->generator->toRoute('admin.foo');
        $this->assertSame('/wp-admin/admin.php?page=foo', $url);
    }
    
    /** @test */
    public function admin_routes_strip_a_possible_trailing_slash_in_the_route_definition()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('admin.php/foo/', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function admin_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('admin/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function options_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('options/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request =
            $this->adminRequest('GET', 'foo', 'options-general.php');
        $this->assertResponse('foo', $request);
    }
    
    /**
     * ALIASING ADMIN ROUTES
     */
    
    /** @test */
    public function tools_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('tools/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'tools.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function users_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('users/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'users.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function plugins_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('plugins/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'plugins.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function themes_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('themes/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'themes.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function comments_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('comments/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request =
            $this->adminRequest('GET', 'foo', 'edit-comments.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function upload_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('upload/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'upload.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function edit_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('posts/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'edit.php');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function index_php_routes_can_be_aliases_for_convenience()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('dashboard/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $request = $this->adminRequest('GET', 'foo', 'index.php');
        $this->assertResponse('foo', $request);
    }
    
}