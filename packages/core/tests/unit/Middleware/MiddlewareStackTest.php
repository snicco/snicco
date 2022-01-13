<?php

declare(strict_types=1);

namespace Tests\Core\unit\Middleware;

use InvalidArgumentException;
use Tests\Core\RoutingTestCase;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Tests\Core\fixtures\Middleware\BazMiddleware;
use Snicco\Core\Middleware\Internal\MiddlewareStack;
use Tests\Core\fixtures\Middleware\FoobarMiddleware;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;

final class MiddlewareStackTest extends RoutingTestCase
{
    
    /** @test */
    public function global_middleware_is_always_run_when_a_route_matches()
    {
        $this->withNewMiddlewareStack(new MiddlewareStack());
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        
        $response = $this->runKernel($this->frontendRequest('GET', '/foo'));
        
        $this->assertSame(
            RoutingTestController::static.':bar_middleware:foo_middleware',
            $response->body()
        );
    }
    
    /** @test */
    public function global_middleware_is_not_run_when_no_route_matches()
    {
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        
        $response = $this->runKernel($this->frontendRequest('GET', '/bar'));
        
        $this->assertSame(
            '',
            $response->body()
        );
    }
    
    /** @test */
    public function global_middleware_can_be_configured_to_run_for_even_for_non_matching_requests()
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::GLOBAL_MIDDLEWARE,
            ])
        );
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        
        $response = $this->runKernel($this->frontendRequest('GET', '/bar'));
        
        $this->assertSame(
            ':bar_middleware:foo_middleware',
            $response->body()
        );
    }
    
    /** @test */
    public function web_middleware_can_be_configured_to_always_run_for_non_matching_requests()
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::WEB_MIDDLEWARE,
            ])
        );
        $this->withMiddlewareGroups(
            [RoutingConfigurator::WEB_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class]]
        );
        
        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);
        
        $response = $this->runKernel($this->frontendRequest('GET', '/bar'));
        
        $this->assertSame(
            ':bar_middleware:foo_middleware',
            $response->body()
        );
    }
    
    /** @test */
    public function running_web_middleware_always_is_has_no_effect_on_admin_requests()
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::WEB_MIDDLEWARE,
            ])
        );
        $this->withMiddlewareGroups(
            [RoutingConfigurator::WEB_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class]]
        );
        
        $this->adminRouteConfigurator()->page(
            'admin1',
            'admin.php/foo',
            RoutingTestController::class,
            [],
            null
        );
        
        $response = $this->runKernel($this->adminRequest('GET', '/foo'));
        $this->assertSame(RoutingTestController::static, $response->body());
        
        $response = $this->runKernel($this->adminRequest('GET', '/bar'));
        $this->assertSame('', $response->body());
    }
    
    /** @test */
    public function admin_middleware_can_be_configured_to_always_run_for_non_matching_requests()
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::ADMIN_MIDDLEWARE,
            ])
        );
        $this->withMiddlewareGroups(
            [RoutingConfigurator::ADMIN_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class]]
        );
        
        $this->adminRouteConfigurator()->page(
            'r1',
            'admin.php/foo',
            RoutingTestController::class,
            [],
            null
        );
        
        $response = $this->runKernel($this->adminRequest('GET', '/bar'));
        
        $this->assertSame(
            ':bar_middleware:foo_middleware',
            $response->body()
        );
        
        $response = $this->runKernel($this->adminRequest('GET', '/foo'));
        
        // The matching admin route does not run the global admin middleware.
        // In the RouteLoader we add the admin middleware by default to all admin requests
        // but this is customizable so we don't force it.
        $this->assertSame(
            RoutingTestController::static,
            $response->body()
        );
    }
    
    /** @test */
    public function running_admin_middleware_always_has_no_effect_on_non_matching_web_requests()
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::ADMIN_MIDDLEWARE,
            ])
        );
        $this->withMiddlewareGroups(
            [RoutingConfigurator::ADMIN_MIDDLEWARE => [FooMiddleware::class, BarMiddleware::class]]
        );
        
        $this->adminRouteConfigurator()->get(
            'web1',
            '/foo',
            RoutingTestController::class
        );
        
        $response = $this->runKernel($this->frontendRequest('GET', '/bar'));
        $this->assertSame('', $response->body());
    }
    
    /** @test */
    public function adding_one_of_the_non_core_middleware_groups_to_always_run_global_will_thrown_an_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can not be used as middleware that is always');
        
        $m = new MiddlewareStack([
            FooMiddleware::class,
        ]);
    }
    
    /** @test */
    public function no_middleware_is_run_if_middleware_is_disabled()
    {
        $m = new MiddlewareStack([
            RoutingConfigurator::WEB_MIDDLEWARE,
            RoutingConfigurator::GLOBAL_MIDDLEWARE,
            RoutingConfigurator::ADMIN_MIDDLEWARE,
        ]);
        
        $m->withMiddlewareGroup(RoutingConfigurator::WEB_MIDDLEWARE, [FooMiddleware::class]);
        $m->withMiddlewareGroup(RoutingConfigurator::ADMIN_MIDDLEWARE, [BarMiddleware::class]);
        $m->withMiddlewareGroup(RoutingConfigurator::GLOBAL_MIDDLEWARE, [BazMiddleware::class]);
        
        $this->withNewMiddlewareStack($m);
        
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class)->middleware(
            FoobarMiddleware::class
        );
        $this->routeConfigurator()->get('bar', '/bar', RoutingTestController::class);
        
        // matching request, Foobar and Baz(global) is run. Web is not run because we are not using the route loader.
        $response = $this->runKernel($this->frontendRequest('GET', '/foo'));
        $this->assertSame(
            RoutingTestController::static.':foobar_middleware:baz_middleware',
            $response->body()
        );
        
        // non-matching request: Foo(web) and Baz(global) are run
        $response = $this->runKernel($this->frontendRequest('GET', '/bogus'));
        $this->assertSame(
            ':foo_middleware:baz_middleware',
            $response->body()
        );
        
        // Now we disable middleware
        $m->disableAllMiddleware();
        
        // only controller run for matching route.
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('GET', '/foo')
        );
        
        // nothing run for non-matching route.
        $this->assertResponseBody('', $this->frontendRequest('GET', '/bogus'));
    }
    
}