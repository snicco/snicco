<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use WP;
use Mockery;
use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\TestDoubles\TestRequest;
use Snicco\EventDispatcher\Listeners\FilterWpQuery;
use Snicco\EventDispatcher\Events\WPQueryFilterable;

class RouteCachingTest extends RoutingTestCase
{
    
    private string $route_cache_file;
    
    protected function setUp() :void
    {
        parent::setUp();
        
        $this->route_cache_file = __DIR__.DS.'__generated_snicco_wp_routes.php';
        
        $this->assertFalse(file_exists($this->route_cache_file));
        
        $this->createCachedRouteCollection($this->route_cache_file);
    }
    
    protected function tearDown() :void
    {
        parent::tearDown();
        
        if (file_exists($this->route_cache_file)) {
            unlink($this->route_cache_file);
        }
    }
    
    /** @test */
    public function a_route_can_be_run_when_no_cache_files_exist_yet()
    {
        $this->createRoutes(function () {
            $this->router->get('foo', Controller::class.'@handle');
        });
        
        $this->assertResponse('foo', $this->frontendRequest('GET', 'foo'));
    }
    
    /** @test */
    public function a_cache_file_is_created_after_the_routes_are_loaded_for_the_first_time()
    {
        $this->createRoutes(function () {
            $this->router->get('foo', Controller::class.'@handle');
        });
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
        
        $this->assertTrue(file_exists($this->route_cache_file));
    }
    
    /** @test */
    public function routes_can_be_read_from_the_cache_and_match_without_needing_to_define_them()
    {
        // Creates the cache file
        $this->createRoutes(function () {
            $this->router->get('foo', Controller::class.'@handle');
            $this->router->get('bar', Controller::class.'@handle');
            $this->router->get('baz', Controller::class.'@handle');
            $this->router->get('biz', Controller::class.'@handle');
            $this->router->get('boo', Controller::class.'@handle');
            $this->router->get('teams/{team}', Controller::class.'@handle');
        });
        
        // New route collection from cache;
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', 'bar');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', 'biz');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', 'baz');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', 'boo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('GET', '/teams/dortmund');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function caching_works_with_closure_route_actions()
    {
        $class = new Controller();
        
        $this->createRoutes(function () use ($class) {
            $this->router->get('foo', function () use ($class) {
                return $class->handle();
            });
        });
        
        $this->assertTrue(file_exists($this->route_cache_file));
        
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function a_route_with_conditions_can_be_cached()
    {
        $this->createRoutes(function () {
            $this->router->get('*', Controller::class.'@handle')->where('maybe', true);
            $this->router->post('*', Controller::class.'@handle')->where('maybe', false);
        });
        
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('POST', 'foo');
        $this->assertEmptyResponse($request);
    }
    
    /** @test */
    public function a_route_with_wordpress_query_filter_can_be_cached_and_read_from_cache()
    {
        $this->createRoutes(function () {
            $this->router->get('foo', function () {
                return 'foo';
            })->wpquery(function () {
                return [
                    'foo' => 'baz',
                ];
            });
        });
        
        $wp = Mockery::mock(WP::class);
        
        // from cache
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $wp->query_vars = ['foo' => 'bar'];
        
        $event = new WPQueryFilterable($request = TestRequest::from('GET', 'foo'), true, $wp);
        $listener = new FilterWpQuery($this->routes);
        $listener->handle($event);
        $this->assertSame(['foo' => 'baz'], $wp->query_vars);
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function reverse_routing_when_no_cache_file_is_created_yet()
    {
        $this->createRoutes(function () {
            $this->router->get('foo', Controller::class.'@handle')->name('foo');
        });
        
        $url_generator = $this->newUrlGenerator();
        
        $this->assertSame('/foo', $url_generator->toRoute('foo', [], false, false));
    }
    
    /** @test */
    public function reverse_routing_works_from_the_cache()
    {
        // Create cache
        $this->createRoutes(function () {
            $this->router->get('foo', Controller::class.'@handle')->name('foo');
            $this->router->get('bar', Controller::class.'@handle')->name('bar');
        });
        
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $url_generator = $this->newUrlGenerator();
        
        $this->assertSame('/foo', $url_generator->toRoute('foo', [], false, false));
        $this->assertSame('/bar', $url_generator->toRoute('bar', [], false, false));
    }
    
    /** @test */
    public function route_attributes_that_get_changed_after_the_route_got_instantiated_by_the_router_still_get_cached()
    {
        // Create cache
        $this->createRoutes(function () {
            $this->router->get('foo', Controller::class.'@handle')->name('foo');
            $this->router->get('bar', Controller::class.'@handle')->name('bar');
        });
        
        // Cache is loaded into this router instance
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $url_generator = $this->newUrlGenerator();
        
        $this->assertSame('/foo', $url_generator->toRoute('foo', [], false, false));
        $this->assertSame('/bar', $url_generator->toRoute('bar', [], false, false));
        
        $this->assertResponse('foo', $this->frontendRequest('GET', 'foo'));
        $this->assertResponse('foo', $this->frontendRequest('GET', 'bar'));
    }
    
    /** @test */
    public function cached_routes_work_with_admin_routes()
    {
        // No cache created
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin')->group(function () {
                $this->router->get('admin/foo', function (Request $request) {
                    return $request->input('page');
                });
            });
        });
        
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $request = $this->adminRequest('GET', 'foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function cached_routes_work_with_ajax_routes()
    {
        $this->createRoutes(function () {
            $this->router->prefix('wp-admin/admin-ajax.php')->group(function () {
                $this->router->post('foo_action')->handle(function () {
                    return 'FOO_ACTION';
                });
            });
        });
        
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $ajax_request = $this->adminAjaxRequest('POST', 'foo_action');
        $this->assertResponse('FOO_ACTION', $ajax_request);
    }
    
    /** @test */
    public function routes_with_closure_conditions_can_be_cached()
    {
        $_SERVER['pass_condition'] = true;
        
        $this->createRoutes(function () {
            $this->router->get()
                         ->where(function () {
                             return $_SERVER['pass_condition'];
                         })
                         ->handle(function () {
                             return 'FOO';
                         });
        });
        
        $this->createCachedRouteCollection($this->route_cache_file);
        $this->routes->addToUrlMatcher();
        
        $request = $this->frontendRequest('GET', 'post1');
        $this->assertResponse('FOO', $request);
        
        unset($_SERVER['pass_condition']);
    }
    
}

class Controller
{
    
    public function handle()
    {
        return 'foo';
    }
    
}