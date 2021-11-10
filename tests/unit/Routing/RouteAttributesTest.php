<?php

declare(strict_types=1);

namespace Tests\unit\Routing;

use Mockery;
use Tests\UnitTest;
use Snicco\Support\WP;
use Snicco\Events\Event;
use Snicco\Routing\Router;
use Snicco\View\ViewFactory;
use Tests\stubs\HeaderStack;
use Snicco\Http\Psr7\Request;
use Contracts\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Routing\UrlGenerator;
use Tests\stubs\TestViewFactory;
use Tests\helpers\CreateTestSubjects;
use Tests\helpers\CreateUrlGenerator;
use Tests\helpers\CreateDefaultWpApiMocks;
use Tests\fixtures\Middleware\GlobalMiddleware;
use Snicco\ExceptionHandling\Exceptions\ConfigurationException;

class RouteAttributesTest extends UnitTest
{
    
    use CreateTestSubjects;
    use CreateDefaultWpApiMocks;
    use CreateUrlGenerator;
    
    const controller_namespace = 'Tests\fixtures\Controllers\Web';
    
    private ContainerAdapter $container;
    private Router           $router;
    
    /** @test */
    public function basic_get_routing_works()
    {
        $count = 0;
        
        $this->createRoutes(function () use (&$count) {
            
            $this->router->get('/foo', function () use (&$count) {
                
                $count++;
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $this->assertSame(1, $count);
        
        $request = $this->webRequest('HEAD', '/foo');
        $this->runAndAssertOutput('', $request);
        // Also runs for HEAD methods but without output.
        $this->assertSame(2, $count);
        
    }
    
    /** @test */
    public function basic_post_routing_works()
    {
        
        $this->createRoutes(function () {
            
            $this->router->post('/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('POST', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /** @test */
    public function basic_put_routing_works()
    {
        
        $this->createRoutes(function () {
            
            $this->router->put('/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('PUT', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /** @test */
    public function basic_patch_routing_works()
    {
        
        $this->createRoutes(function () {
            
            $this->router->patch('/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('PATCH', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /** @test */
    public function basic_delete_routing_works()
    {
        
        $this->createRoutes(function () {
            
            $this->router->delete('/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('DELETE', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /** @test */
    public function basic_options_routing_works()
    {
        
        $this->createRoutes(function () {
            
            $this->router->options('/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('OPTIONS', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /** @test */
    public function a_route_can_match_all_methods()
    {
        
        $this->createRoutes(function () {
            
            $this->router->any('/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('POST', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('PUT', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('PATCH', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('DELETE', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('OPTIONS', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /** @test */
    public function a_route_can_match_specific_methods()
    {
        
        $this->createRoutes(function () {
            
            $this->router->match(['GET', 'POST'], '/foo', function () {
                
                return 'foo';
                
            });
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('POST', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('PUT', '/foo');
        $this->runAndAssertOutput('', $request);
        
    }
    
    /** @test */
    public function the_route_handler_can_be_defined_with_a_separate_method()
    {
        
        $this->createRoutes(function () {
            
            $this->router->get('foo')->handle(function () {
                
                return 'foo';
            });
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    /**
     * @test
     * Failed conditions on a matching static route by url will lead to no route matching.
     */
    public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence()
    {
        
        $this->createRoutes(function () {
            
            $this->router->post('/foo/bar', function () {
                
                return 'foo_bar_static';
                
            })->where('false');
            
            $this->router->post('/foo/baz', function () {
                
                return 'foo_baz_static';
                
            });
            
            $this->router->post('/foo/{dynamic}', function () {
                
                return 'dynamic_route';
                
            });
            
        });
        
        // failed condition
        $request = $this->webRequest('POST', '/foo/bar');
        $this->runAndAssertOutput('', $request);
        
        $request = $this->webRequest('POST', '/foo/baz');
        $this->runAndAssertOutput('foo_baz_static', $request);
        
        $request = $this->webRequest('POST', '/foo/biz');
        $this->runAndAssertOutput('dynamic_route', $request);
        
    }
    
    /** @test */
    public function http_verbs_can_be_defined_after_attributes_and_finalize_the_route()
    {
        
        $this->createRoutes(function () {
            
            $this->router->namespace(self::controller_namespace)
                         ->get('/foo', 'RoutingController@foo');
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foobar', $request);
        
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('FOOBAR', $request);
        
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        
        $request = $this->webRequest('POST', '/bar');
        $this->runAndAssertOutput('foobar', $request);
        
        $request = $this->webRequest('PUT', '/baz');
        $this->runAndAssertOutput('FOOBAR', $request);
        
    }
    
    /** @test */
    public function a_route_without_an_action_will_thrown_an_exception()
    {
        
        $this->expectException(ConfigurationException::class);
        
        $this->createRoutes(function () {
            
            $this->router->get('foo');
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foobar', $request);
        
    }
    
    /** @test */
    public function a_route_can_be_set_to_not_handle_anything_but_only_run_middleware()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->createRoutes(function () {
            
            $this->router->get('foo')->noAction()->middleware(GlobalMiddleware::class);
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('', $request);
        
        $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times]);
        
    }
    
    /** @test */
    public function a_no_action_route_can_before_the_http_verb()
    {
        
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;
        
        $this->createRoutes(function () {
            
            $this->router->noAction()->get('foo')->middleware(GlobalMiddleware::class);
            
        });
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('', $request);
        
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('', $request);
        
        $request = $this->webRequest('GET', '/bar');
        $this->runAndAssertOutput('', $request);
        
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo', $request);
        HeaderStack::assertHasStatusCode(200);
        HeaderStack::reset();
        
        $request = $this->webRequest('GET', '/bar');
        $this->runAndAssertOutput('', $request);
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
        
        $request = $this->webRequest('GET', '/foo');
        $this->runAndAssertOutput('foo1', $request);
        
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
        
        $request = $this->webRequest('GET', $url);
        $this->runAndAssertOutput('foo', $request);
        
    }
    
    protected function beforeTestRun()
    {
        
        $this->container = $this->createContainer();
        $this->routes = $this->newCachedRouteCollection();
        $this->container->instance(UrlGenerator::class, $this->newUrlGenerator());
        $this->container->instance(ViewFactory::class, new TestViewFactory());
        $this->container->instance(ResponseFactory::class, $this->createResponseFactory());
        Event::make($this->container);
        Event::fake();
        WP::setFacadeContainer($this->container);
        HeaderStack::reset();
        
    }
    
    protected function beforeTearDown()
    {
        
        Event::setInstance(null);
        Mockery::close();
        WP::reset();
        
    }
    
}

