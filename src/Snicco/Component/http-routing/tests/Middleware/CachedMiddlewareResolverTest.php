<?php

declare(strict_types=1);


namespace Snicco\Component\HttpRouting\Tests\Middleware;

use LogicException;
use Snicco\Component\HttpRouting\Controller\ControllerAction;
use Snicco\Component\HttpRouting\Middleware\MiddlewareBlueprint;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BazMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

final class CachedMiddlewareResolverTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function the_middleware_resolver_can_be_constructed_from_cache(): void
    {
        $blueprint1 = MiddlewareBlueprint::from(FooMiddleware::class, ['bar', 'baz']);
        $blueprint2 = MiddlewareBlueprint::from(BarMiddleware::class, ['biz']);

        $route_cache = ['r1' => [$blueprint1->asArray(), $blueprint2->asArray()]];

        $resolver = MiddlewareResolver::fromCache($route_cache, [
            RoutingConfigurator::GLOBAL_MIDDLEWARE => [$blueprint1->asArray()],
            RoutingConfigurator::FRONTEND_MIDDLEWARE => [$blueprint2->asArray()]
        ]);

        $route = Route::create('/', Route::DELEGATE, 'r1');
        $route->middleware(BazMiddleware::class);
        $controller_action = new ControllerAction(Route::DELEGATE, $this->psr_container);

        // BazMiddleware is not present since It's loaded from cache.
        $this->assertEquals(
            [$blueprint1, $blueprint2],
            $resolver->resolveForRoute($route, $controller_action)
        );

        $this->assertEquals(
            [$blueprint1, $blueprint2],
            $resolver->resolveForRequestWithoutRoute($this->frontendRequest())
        );

        $this->assertEquals(
            [$blueprint1],
            $resolver->resolveForRequestWithoutRoute($this->adminRequest('/foo'))
        );

        $this->assertEquals(
            [$blueprint1],
            $resolver->resolveForRequestWithoutRoute(
                $this->adminRequest('/foo')
            )
        );
    }

    /**
     * @test
     */
    public function middleware_for_api_requests_can_be_loaded_from_cache(): void
    {
        $blueprint1 = MiddlewareBlueprint::from(FooMiddleware::class, ['bar', 'baz']);
        $blueprint2 = MiddlewareBlueprint::from(BarMiddleware::class, ['biz']);

        $resolver = MiddlewareResolver::fromCache([], [
            RoutingConfigurator::GLOBAL_MIDDLEWARE => [$blueprint1->asArray()],
            RoutingConfigurator::API_MIDDLEWARE => [$blueprint2->asArray()]
        ]);

        $this->assertEquals(
            [$blueprint1, $blueprint2],
            $resolver->resolveForRequestWithoutRoute($this->apiRequest())
        );

        $this->assertEquals(
            [$blueprint1],
            $resolver->resolveForRequestWithoutRoute($this->adminRequest('/foo'))
        );

        $this->assertEquals(
            [$blueprint1],
            $resolver->resolveForRequestWithoutRoute(
                $this->frontendRequest('/foo')
            )
        );
    }

    /**
     * @test
     */
    public function test_exception_if_a_route_is_not_in_cache(): void
    {
        $blueprint1 = MiddlewareBlueprint::from(FooMiddleware::class, ['bar', 'baz']);
        $blueprint2 = MiddlewareBlueprint::from(BarMiddleware::class, ['biz']);

        $route_cache = ['r2' => [$blueprint1->asArray(), $blueprint2->asArray()]];

        $resolver = MiddlewareResolver::fromCache($route_cache, [
            RoutingConfigurator::GLOBAL_MIDDLEWARE => [$blueprint1->asArray()],
            RoutingConfigurator::FRONTEND_MIDDLEWARE => [$blueprint2->asArray()]
        ]);

        $route = Route::create('/', Route::DELEGATE, 'r1');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The middleware resolver is cached but has no entry for route [r1].');
        $resolver->resolveForRoute($route, new ControllerAction(Route::DELEGATE, $this->psr_container));
    }

}