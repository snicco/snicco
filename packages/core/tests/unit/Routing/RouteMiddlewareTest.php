<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Snicco\Http\Psr7\Request;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\FooBarMiddleware;

class RouteMiddlewareTest extends RoutingTestCase
{
    
    /** @test */
    public function applying_a_route_group_to_a_route_applies_all_middleware_in_the_group()
    {
        $this->withMiddlewareGroups([
            'foobar' => [
                FooMiddleware::class,
                BarMiddleware::class,
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
                FooMiddleware::class,
                BarMiddleware::class,
            
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
                    FooMiddleware::class,
                    BarMiddleware::class,
                ],
                'foobar' => [
                    FooMiddleware::class,
                    BarMiddleware::class,
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
                FooMiddleware::class.':FOO',
                BarMiddleware::class,
                BazMiddleware::class,
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
                FooMiddleware::class,
            ],
            'bar' => [
                BarMiddleware::class,
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
                FooMiddleware::class,
                BarMiddleware::class,
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
                BazMiddleware::class,
                'bar_group',
            ],
            'bar_group' => [
                BarMiddleware::class,
                'foo_group',
            ],
            'foo_group' => [
                FooMiddleware::class,
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
            })->middleware(FooBarMiddleware::class.':FOO,BAR');
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
                             })->middleware(FooMiddleware::class);
                         });
        });
        
        $this->withMiddlewareGroups([
            'barbaz' => [
                BazMiddleware::class,
                BarMiddleware::class,
            ],
        ]);
        
        $this->withMiddlewarePriority([
            
            FooMiddleware::class,
            BarMiddleware::class,
            BazMiddleware::class,
        
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
                FooBarMiddleware::class,
                BarMiddleware::class,
                BazMiddleware::class,
                FooMiddleware::class,
            ],
        ]);
        
        $this->withMiddlewarePriority([
            
            FooMiddleware::class,
            BarMiddleware::class,
        
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponse('foobarfoobarbaz', $request);
    }
    
}