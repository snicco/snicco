<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Conditions\TrueCondition;
use Tests\Core\fixtures\Conditions\FalseCondition;
use Tests\Core\fixtures\Conditions\UniqueCondition;

class RouteGroupsTest extends RoutingTestCase
{
    
    const namespace = 'Tests\\Core\\fixtures\\Controllers\\Web';
    
    /**
     * ROUTE GROUPS
     */
    
    /** @test */
    public function methods_can_be_merged_for_a_group()
    {
        $this->createRoutes(function () {
            $this->router
                ->methods(['GET', 'PUT'])
                ->group(function () {
                    $this->router->post('/foo')->handle(function () {
                        return 'post_foo';
                    });
                });
        });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('post_foo', $get_request);
        
        $put_request = $this->frontendRequest('PUT', '/foo');
        $this->assertResponse('post_foo', $put_request);
        
        $post_request = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('post_foo', $post_request);
        
        $patch_request = $this->frontendRequest('PATCH', '/foo');
        $this->assertEmptyResponse($patch_request);
    }
    
    /** @test */
    public function middleware_is_merged_for_route_groups()
    {
        $this->createRoutes(function () {
            $this->router
                ->middleware('foo:FOO')
                ->group(function () {
                    $this->router
                        ->get('/foo')
                        ->middleware('bar:BAR')
                        ->handle(function (Request $request) {
                            return $request->body;
                        });
                    
                    $this->router
                        ->post('/foo')
                        ->handle(function (Request $request) {
                            return $request->body;
                        });
                });
        });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('FOOBAR', $get_request);
        
        $post_request = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('FOO', $post_request);
    }
    
    /** @test */
    public function the_group_namespace_is_applied_to_child_routes()
    {
        $this->createRoutes(function () {
            $this->router
                ->namespace(self::namespace)
                ->group(function () {
                    $this->router->get('/foo')->handle('RoutingController@foo');
                });
        });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $get_request);
    }
    
    /** @test */
    public function a_group_can_prefix_all_child_route_urls()
    {
        $this->createRoutes(function () {
            $this->router
                ->prefix('foo')
                ->group(function () {
                    $this->router->get('bar', function () {
                        return 'foobar';
                    });
                    
                    $this->router->get('baz', function () {
                        return 'foobaz';
                    });
                });
        });
        
        $this->assertResponse('foobar', $this->frontendRequest('GET', '/foo/bar'));
        $this->assertResponse('foobaz', $this->frontendRequest('GET', '/foo/baz'));
        $this->assertEmptyResponse($this->frontendRequest('GET', '/foo'));
    }
    
    /** @test */
    public function group_conditions_are_merged_into_child_routes()
    {
        $this->createRoutes(function () {
            $this->router
                ->where('maybe', false)
                ->namespace('Tests\stubs\Controllers\Web')
                ->group(function () {
                    $this->router
                        ->get()
                        ->where(new FalseCondition())
                        ->handle('RoutingController@foo');
                    
                    $this->router
                        ->post()
                        ->where(new TrueCondition())
                        ->handle('RoutingController@foo');
                });
        });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertEmptyResponse($get_request);
        
        $post_request = $this->frontendRequest('POST', '/foo');
        $this->assertEmptyResponse($post_request);
    }
    
    /** @test */
    public function duplicate_conditions_a_removed_during_route_compilation()
    {
        $this->createRoutes(function () {
            $this->router
                ->where(new UniqueCondition('foo'))
                ->group(function () {
                    $this->router
                        ->get('/*', function () {
                            return 'get_foo';
                        })
                        ->where(new UniqueCondition('foo'));
                });
        });
        
        $this->assertResponse('get_foo', $this->frontendRequest('GET', '/foo'));
        
        $count = $GLOBALS['test']['unique_condition'];
        $this->assertSame(1, $count, 'Condition was run: '.$count.' times.');
    }
    
    /** @test */
    public function unique_conditions_are_also_enforced_when_conditions_are_aliased()
    {
        $this->createRoutes(function () {
            $this->router
                ->where('unique', 'foo')
                ->group(function () {
                    $this->router
                        ->get('/*', function () {
                            return 'get_bar';
                        })
                        ->where('unique', 'foo');
                });
        });
        
        $this->assertResponse('get_bar', $this->frontendRequest('GET', '/bar'));
        
        $count = $GLOBALS['test']['unique_condition'];
        $this->assertSame(1, $count, 'Condition was run: '.$count.' times.');
    }
    
    /** @test */
    public function methods_are_merged_on_multiple_levels()
    {
        $this->createRoutes(function () {
            $this->router
                ->methods('GET')
                ->group(function () {
                    $this->router->methods('POST')->group(function () {
                        $this->router->put('/foo')->handle(function () {
                            return 'foo';
                        });
                    });
                    
                    $this->router->patch('/bar')->handle(function () {
                        return 'bar';
                    });
                });
        });
        
        // First route
        $post = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('foo', $post);
        
        $put = $this->frontendRequest('PUT', '/foo');
        $this->assertResponse('foo', $put);
        
        $get = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $get);
        
        $patch = $this->frontendRequest('PATCH', '/foo');
        $this->assertEmptyResponse($patch);
        
        // Second route
        $get = $this->frontendRequest('GET', '/bar');
        $this->assertResponse('bar', $get);
        
        $patch = $this->frontendRequest('PATCH', '/bar');
        $this->assertResponse('bar', $patch);
        
        $post = $this->frontendRequest('POST', '/bar');
        $this->assertEmptyResponse($post);
        
        $put = $this->frontendRequest('PUT', '/bar');
        $this->assertEmptyResponse($put);
    }
    
    /** @test */
    public function middleware_is_nested_on_multiple_levels()
    {
        $this->createRoutes(function () {
            $this->router
                ->middleware('foo:FOO')
                ->group(function () {
                    $this->router->middleware('bar:BAR')->group(function () {
                        $this->router
                            ->get('/foo')
                            ->middleware('baz:BAZ')
                            ->handle(function (Request $request) {
                                return $request->body;
                            });
                    });
                    
                    $this->router
                        ->get('/bar')
                        ->middleware('baz:BAZ')
                        ->handle(function (Request $request) {
                            return $request->body;
                        });
                });
        });
        
        $get = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('FOOBARBAZ', $get);
        
        $get = $this->frontendRequest('GET', '/bar');
        $this->assertResponse('FOOBAZ', $get);
    }
    
    /**
     * NESTED ROUTE GROUPS
     */
    
    /** @test */
    public function the_namespace_is_always_overwritten_by_child_routes()
    {
        $this->createRoutes(function () {
            $this->router
                ->namespace('Tests\FalseNamespace')
                ->group(function () {
                    $this->router
                        ->namespace(self::namespace)
                        ->get('/foo')
                        ->handle('RoutingController@foo');
                });
        });
        
        $get_request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $get_request);
    }
    
    /** @test */
    public function group_prefixes_are_merged_on_multiple_levels()
    {
        $this->createRoutes(function () {
            $this->router
                ->prefix('foo')
                ->group(function () {
                    $this->router->prefix('bar')->group(function () {
                        $this->router->get('baz', function () {
                            return 'foobarbaz';
                        });
                    });
                    
                    $this->router->get('biz', function () {
                        return 'foobiz';
                    });
                });
        });
        
        $this->assertResponse('foobarbaz', $this->frontendRequest('GET', '/foo/bar/baz'));
        
        $this->assertResponse('foobiz', $this->frontendRequest('GET', '/foo/biz'));
        
        $this->assertEmptyResponse($this->frontendRequest('GET', '/foo/bar/biz'));
    }
    
    /** @test */
    public function conditions_are_merged_on_multiple_levels()
    {
        // Given
        $GLOBALS['test']['parent_condition_called'] = false;
        $GLOBALS['test']['child_condition_called'] = false;
        
        $this->createRoutes(function () {
            $this->router
                ->where(function () {
                    $GLOBALS['test']['parent_condition_called'] = true;
                    
                    $this->assertFalse($GLOBALS['test']['child_condition_called']);
                    
                    return true;
                })
                ->group(function () {
                    $this->router
                        ->get()
                        ->where('true')
                        ->handle(function () {
                            return 'GET';
                        });
                    
                    $this->router->where(function () {
                        $GLOBALS['test']['child_condition_called'] = true;
                        
                        return false;
                    })->group(function () {
                        $this->router
                            ->post()
                            ->where('true')
                            ->handle(function () {
                                $this->fail('This route should not have been called');
                            });
                    });
                });
        });
        
        // When
        $get = $this->frontendRequest('GET', '/foo');
        
        // Then
        $this->assertResponse('GET', $get);
        $this->assertSame(true, $GLOBALS['test']['parent_condition_called']);
        $this->assertSame(false, $GLOBALS['test']['child_condition_called']);
        
        // Given
        $GLOBALS['test']['parent_condition_called'] = false;
        $GLOBALS['test']['child_condition_called'] = false;
        
        // When
        $post = $this->frontendRequest('POST', '/foo');
        
        // Then
        $this->assertResponse('', $post);
        $this->assertSame(true, $GLOBALS['test']['parent_condition_called']);
        $this->assertSame(true, $GLOBALS['test']['child_condition_called']);
    }
    
    /** @test */
    public function the_first_matching_route_aborts_the_iteration_over_all_current_routes()
    {
        $GLOBALS['test']['first_route_condition'] = false;
        
        $this->createRoutes(function () {
            $this->router->group(function () {
                $this->router
                    ->get('/*')
                    ->where(function () {
                        $GLOBALS['test']['first_route_condition'] = true;
                        
                        return true;
                    })
                    ->handle(function () {
                        return 'bar1';
                    });
                
                $this->router
                    ->get('/*')
                    ->where(function () {
                        $this->fail(
                            'Route condition evaluated even tho we already had a matching route'
                        );
                    })
                    ->handle(function () {
                        return 'bar2';
                    });
            });
        });
        
        $this->assertResponse('bar1', $this->frontendRequest('GET', '/foo/bar'));
        
        $this->assertTrue($GLOBALS['test']['first_route_condition']);
    }
    
}

