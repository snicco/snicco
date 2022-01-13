<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Tests\Core\fixtures\Middleware\FoobarMiddleware;
use Tests\Core\fixtures\Middleware\BooleanMiddleware;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

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
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'foobar'
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        
        // Foo middleware is run first, so it appends last to the response body
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:foo_middleware',
            $request
        );
    }
    
    /** @test */
    public function middleware_in_the_global_group_is_always_applied()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->withMiddlewareGroups([
            'global' => [
                FooMiddleware::class,
                BarMiddleware::class,
            
            ],
        ]);
        
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:foo_middleware',
            $request
        );
    }
    
    /** @test */
    public function duplicate_middleware_is_filtered_out()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'foobar'
        );
        
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
        
        // The middleware is not run twice.
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:foo_middleware',
            $request
        );
    }
    
    /** @test */
    public function duplicate_middleware_is_filtered_out_when_passing_the_same_middleware_arguments()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            ['all', 'foo:FOO']
        );
        
        $this->withMiddlewareGroups([
            'all' => [
                FooMiddleware::class.':FOO',
                BarMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', 'foo');
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:FOO',
            $request
        );
    }
    
    /** @test */
    public function duplicate_middleware_is_not_filtered_out_when_passing_different_arguments()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            ['all', 'foo:FOO1']
        );
        
        $this->withMiddlewareGroups([
            'all' => [
                FooMiddleware::class.':FOO2',
                BarMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', 'foo');
        
        // The middleware on the route is run last which is why is output is appended first to the response body.
        $this->assertResponseBody(
            RoutingTestController::static.':FOO1:bar_middleware:FOO2',
            $request
        );
    }
    
    /** @test */
    public function multiple_middleware_groups_can_be_applied()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)
             ->middleware(['foo', 'bar']);
        
        $this->withMiddlewareGroups([
            'foo' => [
                FooMiddleware::class,
            ],
            'bar' => [
                BarMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:foo_middleware',
            $request
        );
    }
    
    /**
     * @test
     * @todo Bad middleware is currently only detected at runtime when running the route.
     */
    public function unknown_middleware_throws_an_exception()
    {
        $this->expectExceptionMessage('The middleware alias [abc] does not exist.');
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)
             ->middleware('abc');
        
        $this->runKernel($this->frontendRequest('GET', 'foo'));
    }
    
    /** @test */
    public function multiple_middleware_arguments_can_be_passed()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)
             ->middleware('foobar');
        
        $this->routeConfigurator()->post('r2', '/foo', RoutingTestController::class)
             ->middleware('foobar:FOO');
        
        $this->routeConfigurator()->patch('r3', '/foo', RoutingTestController::class)
             ->middleware('foobar:FOO,BAR');
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':foobar_middleware', $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':FOO_foobar_middleware', $request);
        
        $request = $this->frontendRequest('PATCH', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':FOO_BAR', $request);
    }
    
    /** @test */
    public function boolean_true_false_is_converted()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)
             ->middleware(BooleanMiddleware::class.':true');
        
        $this->routeConfigurator()->post('r2', '/foo', RoutingTestController::class)
             ->middleware(BooleanMiddleware::class.':false');
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':boolean_true', $request);
        
        $request = $this->frontendRequest('POST', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':boolean_false', $request);
    }
    
    /** @test */
    public function a_middleware_group_can_point_to_a_middleware_alias()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'foogroup'
        );
        
        $this->withMiddlewareGroups([
            
            'foogroup' => [
                'foo',
            ],
        
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(RoutingTestController::static.':foo_middleware', $request);
    }
    
    /** @test */
    public function group_and_route_middleware_can_be_combined()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            ['baz', 'foobar']
        );
        
        $this->withMiddlewareGroups([
            'foobar' => [
                FooMiddleware::class,
                BarMiddleware::class,
            ],
        ]);
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(
            RoutingTestController::static.':bar_middleware:foo_middleware:baz_middleware',
            $request
        );
    }
    
    /** @test */
    public function a_middleware_group_can_contain_another_middleware_group()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'baz_group'
        );
        
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
        $this->assertResponseBody(
            RoutingTestController::static.':foo_middleware:bar_middleware:baz_middleware',
            $request
        );
    }
    
    /** @test */
    public function middleware_can_be_applied_without_an_alias()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            FooMiddleware::class.':FOO'
        );
        
        $request = $this->frontendRequest('GET', '/foo');
        $this->assertResponseBody(
            RoutingTestController::static.':FOO',
            $request
        );
    }
    
    /** @test */
    public function middleware_can_be_sorted()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)
             ->middleware(['barbaz', FooMiddleware::class]);
        
        // The global middleware will be run last even tho it has no priority.
        $this->withGlobalMiddleware([FoobarMiddleware::class]);
        
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
        $this->assertResponseBody(
            RoutingTestController::static
            .':baz_middleware:bar_middleware:foo_middleware:foobar_middleware',
            $request
        );
    }
    
    /** @test */
    public function middleware_keeps_its_relative_position_if_its_has_no_priority_defined()
    {
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class)->middleware(
            'all'
        );
        
        $this->withMiddlewareGroups([
            'all' => [
                FoobarMiddleware::class,
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
        $this->assertResponseBody(
            RoutingTestController::static
            .':baz_middleware:foobar_middleware:bar_middleware:foo_middleware',
            $request
        );
    }
    
}