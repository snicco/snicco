<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Snicco\Core\Http\Psr7\Request;
use Tests\Core\fixtures\Middleware\BarAbstractMiddleware;
use Tests\Core\fixtures\Middleware\BazAbstractMiddleware;
use Tests\Core\fixtures\Middleware\FooAbstractMiddleware;
use Tests\Core\fixtures\Middleware\FooBarAbstractMiddleware;

class RouteMiddlewareTest extends RoutingTestCase
{
    
    /** @test */
    public function applying_a_route_group_to_a_route_applies_all_middleware_in_the_group()
    {
        $this->withMiddlewareGroups([
            'foobar' => [
                FooAbstractMiddleware::class,
                BarAbstractMiddleware::class,
            ],
        ]);
        
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware('foobar');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function middleware_in_the_global_group_is_always_applied()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            });
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->withMiddlewareGroups([
            'global' => [
                FooAbstractMiddleware::class,
                BarAbstractMiddleware::class,
            
            ],
        ]);
        
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function duplicate_middleware_is_filtered_out()
    {
        $this->createRoutes(function () {
            $this->router->middleware('foobar')->get('/foo', function (Request $request) {
                return $request->body;
            });
        });
        
        $this->withMiddlewareGroups(
            [
                'global' => [
                    FooAbstractMiddleware::class,
                    BarAbstractMiddleware::class,
                ],
                'foobar' => [
                    FooAbstractMiddleware::class,
                    BarAbstractMiddleware::class,
                ],
            
            ]
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function duplicate_middleware_is_filtered_out_when_passing_the_same_middleware_arguments()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware(['all', 'foo:FOO']);
        });
        
        $this->withMiddlewareGroups([
            'all' => [
                FooAbstractMiddleware::class.':FOO',
                BarAbstractMiddleware::class,
                BazAbstractMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponse('FOObarbaz', $request);
    }
    
    /** @test */
    public function multiple_middleware_groups_can_be_applied()
    {
        $this->createRoutes(function () {
            $this->router->middleware(['foo', 'bar'])
                         ->get('/foo', function (Request $request) {
                             return $request->body;
                         });
        });
        
        $this->withMiddlewareGroups([
            'foo' => [
                FooAbstractMiddleware::class,
            ],
            'bar' => [
                BarAbstractMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponse('foobar', $request);
    }
    
    /** @test */
    public function unknown_middleware_throws_an_exception()
    {
        $this->expectExceptionMessage('Unknown middleware [abc]');
        
        $this->createRoutes(function () {
            $this->router->middleware('abc')->get('foo', function (Request $request) {
                return $request->body;
            });
        });
        
        $this->runKernel($this->frontendRequest('GET', 'foo'));
    }
    
    /** @test */
    public function multiple_middleware_arguments_can_be_passed()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware('foobar');
            
            $this->router->post('/foo', function (Request $request) {
                return $request->body;
            })->middleware('foobar:FOO');
            
            $this->router->patch('/foo', function (Request $request) {
                return $request->body;
            })->middleware('foobar:FOO,BAR');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobar', $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponse('FOObar', $request);
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertResponse('FOOBAR', $request);
    }
    
    /** @test */
    public function a_middleware_group_can_point_to_a_middleware_alias()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware('foogroup');
        });
        
        $this->withMiddlewareGroups([
            
            'foogroup' => [
                'foo',
            ],
        
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foo', $request);
    }
    
    /** @test */
    public function group_and_route_middleware_can_be_combined()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware(['baz', 'foobar']);
        });
        
        $this->withMiddlewareGroups([
            'foobar' => [
                FooAbstractMiddleware::class,
                BarAbstractMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('bazfoobar', $request);
    }
    
    /** @test */
    public function a_middleware_group_can_contain_another_middleware_group()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware('baz_group');
        });
        
        $this->withMiddlewareGroups([
            
            'baz_group' => [
                BazAbstractMiddleware::class,
                'bar_group',
            ],
            'bar_group' => [
                BarAbstractMiddleware::class,
                'foo_group',
            ],
            'foo_group' => [
                FooAbstractMiddleware::class,
            ],
        
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('bazbarfoo', $request);
    }
    
    /** @test */
    public function middleware_can_be_applied_without_an_alias()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware(FooBarAbstractMiddleware::class.':FOO,BAR');
        });
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('FOOBAR', $request);
    }
    
    /** @test */
    public function non_global_middleware_can_be_sorted()
    {
        $this->createRoutes(function () {
            $this->router->middleware('barbaz')
                         ->group(function () {
                             $this->router->get('/foo', function (Request $request) {
                                 return $request->body;
                             })->middleware(FooAbstractMiddleware::class);
                         });
        });
        
        $this->withMiddlewareGroups([
            'barbaz' => [
                BazAbstractMiddleware::class,
                BarAbstractMiddleware::class,
            ],
        ]);
        
        $this->withMiddlewarePriority([
            
            FooAbstractMiddleware::class,
            BarAbstractMiddleware::class,
            BazAbstractMiddleware::class,
        
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobarbaz', $request);
    }
    
    /** @test */
    public function middleware_keeps_its_relative_position_if_its_has_no_priority_defined()
    {
        $this->createRoutes(function () {
            $this->router->get('/foo', function (Request $request) {
                return $request->body;
            })->middleware('all');
        });
        
        $this->withMiddlewareGroups([
            'all' => [
                FooBarAbstractMiddleware::class,
                BarAbstractMiddleware::class,
                BazAbstractMiddleware::class,
                FooAbstractMiddleware::class,
            ],
        ]);
        
        $this->withMiddlewarePriority([
            
            FooAbstractMiddleware::class,
            BarAbstractMiddleware::class,
        
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobarfoobarbaz', $request);
    }
    
}