<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\MiddlewareStack;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BazMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

final class MiddlewareStackTest extends HttpRunnerTestCase
{

    /**
     * @test
     */
    public function global_middleware_is_always_run_when_a_route_matches(): void
    {
        $this->withNewMiddlewareStack(new MiddlewareStack());
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);

        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);

        $response = $this->runKernel($this->frontendRequest('/foo'));

        $this->assertSame(
            RoutingTestController::static . ':bar_middleware:foo_middleware',
            $response->body()
        );
    }

    /**
     * @test
     */
    public function global_middleware_is_not_run_when_no_route_matches(): void
    {
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);

        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);

        $response = $this->runKernel($this->frontendRequest('/bar'));

        $this->assertSame(
            '',
            $response->body()
        );
    }

    /**
     * @test
     */
    public function global_middleware_can_be_configured_to_run_for_even_for_non_matching_requests(): void
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::GLOBAL_MIDDLEWARE,
            ])
        );
        $this->withGlobalMiddleware([FooMiddleware::class, BarMiddleware::class]);

        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);

        $response = $this->runKernel($this->frontendRequest('/bar'));

        $this->assertSame(
            ':bar_middleware:foo_middleware',
            $response->body()
        );
    }

    /**
     * @test
     */
    public function web_middleware_can_be_configured_to_always_run_for_non_matching_requests(): void
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::FRONTEND_MIDDLEWARE,
            ])
        );
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::FRONTEND_MIDDLEWARE => [
                    FooMiddleware::class,
                    BarMiddleware::class,
                ],
            ]
        );

        $this->routeConfigurator()->get('r1', '/foo', RoutingTestController::class);

        $response = $this->runKernel($this->frontendRequest('/bar'));

        $this->assertSame(
            ':bar_middleware:foo_middleware',
            $response->body()
        );
    }

    /**
     * @test
     */
    public function running_web_middleware_always_is_has_no_effect_on_admin_requests(): void
    {
        $this->withNewMiddlewareStack(
            new MiddlewareStack([
                RoutingConfigurator::FRONTEND_MIDDLEWARE,
            ])
        );
        $this->withMiddlewareGroups(
            [
                RoutingConfigurator::FRONTEND_MIDDLEWARE => [
                    FooMiddleware::class,
                    BarMiddleware::class,
                ],
            ]
        );

        $this->adminRouteConfigurator()->page(
            'admin1',
            'admin.php/foo',
            RoutingTestController::class,
            [],
            null
        );

        $response = $this->runKernel($this->adminRequest('/wp-admin/admin.php?page=foo'));
        $this->assertSame(RoutingTestController::static, $response->body());

        $response = $this->runKernel($this->adminRequest('/bar'));
        $this->assertSame('', $response->body());
    }

    /**
     * @test
     */
    public function admin_middleware_can_be_configured_to_always_run_for_non_matching_requests(): void
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

        $response = $this->runKernel($this->adminRequest('/bar'));

        $this->assertSame(
            ':bar_middleware:foo_middleware',
            $response->body()
        );

        $response = $this->runKernel($this->adminRequest('/wp-admin/admin.php?page=foo'));

        // The matching admin route does not run the global admin middleware.
        // In the RouteLoader we add the admin middleware by default to all admin requests
        // but this is customizable so we don't force it.
        $this->assertSame(
            RoutingTestController::static,
            $response->body()
        );
    }

    /**
     * @test
     */
    public function running_admin_middleware_always_has_no_effect_on_non_matching_web_requests(): void
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

        $response = $this->runKernel($this->frontendRequest('/bar'));
        $this->assertSame('', $response->body());
    }

    /**
     * @test
     */
    public function adding_one_of_the_non_core_middleware_groups_to_always_run_global_will_thrown_an_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('can not be used as middleware that is always');

        $m = new MiddlewareStack([
            FooMiddleware::class,
        ]);
    }

    /**
     * @test
     */
    public function no_middleware_is_run_if_middleware_is_disabled(): void
    {
        $m = new MiddlewareStack([
            RoutingConfigurator::FRONTEND_MIDDLEWARE,
            RoutingConfigurator::GLOBAL_MIDDLEWARE,
            RoutingConfigurator::ADMIN_MIDDLEWARE,
        ]);

        $m->withMiddlewareGroup(RoutingConfigurator::FRONTEND_MIDDLEWARE, [FooMiddleware::class]);
        $m->withMiddlewareGroup(RoutingConfigurator::ADMIN_MIDDLEWARE, [BarMiddleware::class]);
        $m->withMiddlewareGroup(RoutingConfigurator::GLOBAL_MIDDLEWARE, [BazMiddleware::class]);

        $this->withNewMiddlewareStack($m);

        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class)->middleware(
            FoobarMiddleware::class
        );
        $this->routeConfigurator()->get('bar', '/bar', RoutingTestController::class);

        // matching request, Foobar and Baz(global) is run. Web is not run because we are not using the route loader.
        $response = $this->runKernel($this->frontendRequest('/foo'));
        $this->assertSame(
            RoutingTestController::static . ':foobar_middleware:baz_middleware',
            $response->body()
        );

        // non-matching request: Foo(web) and Baz(global) are run
        $response = $this->runKernel($this->frontendRequest('/bogus'));
        $this->assertSame(
            ':foo_middleware:baz_middleware',
            $response->body()
        );

        // Now we disable middleware
        $m->disableAllMiddleware();

        // only controller run for matching route.
        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('/foo')
        );

        // nothing run for non-matching route.
        $this->assertResponseBody('', $this->frontendRequest('/bogus'));
    }

}