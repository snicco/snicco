<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Http\Delegate;
use Snicco\Routing\Router;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Contracts\ContainerAdapter;
use Tests\helpers\CreatesWpUrls;
use Snicco\Contracts\Middleware;
use Tests\helpers\CreateTestSubjects;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateDefaultWpApiMocks;
use Snicco\Middleware\Core\OutputBufferMiddleware;

class AdminRoutesTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreatesWpUrls;
    use CreateDefaultWpApiMocks;
    use CreateUrlGenerator;
    
    private Router $router;
    
    private ContainerAdapter $container;
    
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
        
        $request = $this->adminRequestTo('foo');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('bar');
        $this->runAndAssertOutput('', $request);
        
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
        
        $request = $this->adminRequestTo('foo');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->adminRequestTo('bar');
        $this->runAndAssertOutput('bar', $request);
        
        $request = $this->adminRequestTo('baz');
        $this->runAndAssertOutput('', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'users.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'admin.php');
        $this->runAndAssertOutput('', $request);
        
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
        
        $url = $this->newUrlGenerator()->toRoute('admin.foo');
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
        
        $request = $this->adminRequestTo('foo');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo');
        $this->runAndAssertOutput('foo', $request);
        
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
            $this->adminRequestTo('foo', 'GET', 'options-general.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'tools.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'users.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'plugins.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'themes.php');
        $this->runAndAssertOutput('foo', $request);
        
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
            $this->adminRequestTo('foo', 'GET', 'edit-comments.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'upload.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'edit.php');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->adminRequestTo('foo', 'GET', 'index.php');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newRouteCollection();
        Event::make($this->container);
        Event::fake();
        WP::setFacadeContainer($this->container);
        WP::shouldReceive('isAdmin')->andReturnTrue();
        $this->withoutOutputBufferMiddleware();
        
    }
    
    protected function beforeTearDown()
    {
        
        Mockery::close();
        Event::setInstance(null);
        WP::reset();
        
    }
    
    private function withoutOutputBufferMiddleware() :void
    {
        $this->container->instance(
            OutputBufferMiddleware::class,
            new class extends Middleware
            {
                
                public function handle(Request $request, Delegate $next) :Response
                {
                    return $next($request);
                }
                
            }
        );
    }
    
}