<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\TestDoubles\HeaderStack;
use Tests\Core\fixtures\Middleware\GlobalMiddleware;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class RouteAttributesTest extends RoutingTestCase
{
    
    const controller_namespace = 'Tests\\Core\\fixtures\\Controllers\\Web';
    
    /** @test */
    public function basic_get_routing_works()
    {
        $this->createRoutes(function () use (&$count) {
            $this->router->get('/foo', function () use (&$count) {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function get_routes_match_head_requests()
    {
        $GLOBALS['test']['run_count'] = 0;
        
        $this->createRoutes(function () use (&$count) {
            $this->router->get('/foo', function () use (&$count) {
                $GLOBALS['test']['run_count'] = 1;
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('HEAD', '/foo');
        $this->assertResponse('', $request);
        $this->assertSame(1, $GLOBALS['test']['run_count']);
    }
    
    /** @test */
    public function basic_post_routing_works()
    {
        $this->createRoutes(function () {
            $this->router->post('/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function basic_put_routing_works()
    {
        $this->createRoutes(function () {
            $this->router->put('/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function basic_patch_routing_works()
    {
        $this->createRoutes(function () {
            $this->router->patch('/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function basic_delete_routing_works()
    {
        $this->createRoutes(function () {
            $this->router->delete('/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function basic_options_routing_works()
    {
        $this->createRoutes(function () {
            $this->router->options('/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function a_route_can_match_all_methods()
    {
        $this->createRoutes(function () {
            $this->router->any('/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('DELETE', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('OPTIONS', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function a_route_can_match_specific_methods()
    {
        $this->createRoutes(function () {
            $this->router->match(['GET', 'POST'], '/foo', function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('PUT', '/foo');
        $this->assertResponse('', $request);
    }
    
    /** @test */
    public function the_route_handler_can_be_defined_with_a_separate_method()
    {
        $this->createRoutes(function () {
            $this->router->get('foo')->handle(function () {
                return 'foo';
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /**
     * @test
     * Failed conditions on a matching static route by url will lead to no route matching.
     */
    public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence()
    {
        $this->createRoutes(function () {
            $this->router->post('/foo/baz', function () {
                return 'foo_baz_static';
            });
            
            $this->router->post('/foo/{dynamic}', function () {
                return 'dynamic_route';
            });
        });
        
        $request = $this->frontendRequest('POST', '/foo/baz');
        $this->assertResponse('foo_baz_static', $request);
        
        $request = $this->frontendRequest('POST', '/foo/biz');
        $this->assertResponse('dynamic_route', $request);
    }
    
    /** @test */
    public function http_verbs_can_be_defined_after_attributes_and_finalize_the_route()
    {
        $this->createRoutes(function () {
            $this->router->namespace(self::controller_namespace)
                         ->get('/foo', 'RoutingController@foo');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function middleware_can_be_set()
    {
        $this->createRoutes(function () {
            $this->router
                ->get('/foo')
                ->middleware('foo')
                ->handle(function (Request $request) {
                    return $request->body;
                });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function a_route_can_have_multiple_middlewares()
    {
        $this->createRoutes(function () {
            $this->router
                ->get('/foo')
                ->middleware(['foo', 'bar'])
                ->handle(function (Request $request) {
                    return $request->body;
                });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function middleware_can_pass_arguments()
    {
        $this->createRoutes(function () {
            $this->router
                ->get('/foo')
                ->middleware(['foo:FOO', 'bar:BAR'])
                ->handle(function (Request $request) {
                    return $request->body;
                });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('FOOBAR', $request);
    }
    
    /** @test */
    public function middleware_can_be_set_before_the_http_verb()
    {
        $this->createRoutes(function () {
            $this->router
                ->middleware('foo')
                ->get('/foo')
                ->handle(function (Request $request) {
                    return $request->body;
                });
            
            // As array.
            $this->router
                ->middleware(['foo', 'bar'])
                ->post('/bar')
                ->handle(function (Request $request) {
                    return $request->body;
                });
            
            // With Args
            $this->router
                ->middleware(['foo:FOO', 'bar:BAR'])
                ->put('/baz')
                ->handle(function (Request $request) {
                    return $request->body;
                });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
        
        $request = $this->frontendRequest('POST', '/bar');
        $this->assertResponse('foobar', $request);
        
        $request = $this->frontendRequest('PUT', '/baz');
        $this->assertResponse('FOOBAR', $request);
    }
    
    /** @test */
    public function a_route_without_an_action_will_thrown_an_exception()
    {
        $this->expectException(ConfigurationException::class);
        
        $this->createRoutes(function () {
            $this->router->get('foo');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function a_route_with_url_and_condition_matching_will_throw_an_exception()
    {
        $this->expectException(ConfigurationException::class);
        
        $this->createRoutes(function () {
            $this->router->get('foo')->where(fn() => true)->noAction();
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->runKernel($request);
    }
    
    /** @test */
    public function a_route_with_custom_conditions_and_wpquery_filter_will_throw_an_exception()
    {
        $this->expectException(ConfigurationException::class);
        
        $this->createRoutes(function () {
            $this->router->get()->where(fn() => true)->wpquery(fn() => [])->noAction();
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->runKernel($request);
    }
    
    /** @test */
    public function a_route_can_be_set_to_not_handle_anything_but_only_run_middleware()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->createRoutes(function () {
            $this->router->get('foo')->noAction()->middleware(GlobalMiddleware::class);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times]);
    }
    
    /** @test */
    public function a_no_action_route_can_before_the_http_verb()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->createRoutes(function () {
            $this->router->noAction()->get('foo')->middleware(GlobalMiddleware::class);
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times]);
    }
    
    /** @test */
    public function a_no_action_route_group_can_be_added()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->createRoutes(function () {
            $this->router->noAction()->group(function () {
                $this->router->name('a')->group(function () {
                    $this->router->get('foo')->middleware(GlobalMiddleware::class);
                });
                
                $this->router->get('bar')->middleware(GlobalMiddleware::class);
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('', $request);
        
        $request = $this->frontendRequest('GET', '/bar');
        $this->assertResponse('', $request);
        
        $this->assertSame(2, $GLOBALS['test'][GlobalMiddleware::run_times]);
    }
    
    /** @test */
    public function a_no_action_group_can_be_overwritten()
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->createRoutes(function () {
            $this->router->noAction()->group(function () {
                $this->router->get('foo', function () {
                    return 'foo';
                });
                
                $this->router->get('bar')->middleware(GlobalMiddleware::class);
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
        HeaderStack::assertHasStatusCode(200);
        HeaderStack::reset();
        
        $request = $this->frontendRequest('GET', '/bar');
        $this->assertResponse('', $request);
        $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times]);
        HeaderStack::assertNoStatusCodeSent();
        HeaderStack::reset();
    }
    
    /** @test */
    public function a_route_for_the_same_method_and_url_cant_be_added_twice_and_wont_throw_an_exception()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function () {
                return 'foo1';
            });
            
            $this->router->get('/foo', function () {
                return 'foo2';
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo1', $request);
    }
    
    /** @test */
    public function a_route_with_the_same_name_cant_be_added_twice_even_if_urls_are_different()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function () {
                return 'foo';
            })->name('route1');
            
            $this->router->get('/bar', function () {
                return 'bar';
            })->name('route1');
        });
        
        $url = $this->newUrlGenerator()->toRoute('route1');
        
        $request = $this->frontendRequest('GET', $url);
        $this->assertResponse('foo', $request);
    }
    
}

