<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Middleware;

use LogicException;
use Pimple\Container;
use Snicco\Component\HttpRouting\Controller\ControllerAction;
use Snicco\Component\HttpRouting\Exception\MiddlewareRecursion;
use Snicco\Component\HttpRouting\Middleware\MiddlewareBlueprint;
use Snicco\Component\HttpRouting\Middleware\MiddlewareResolver;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\BazMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\ControllerWithBarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class CachedMiddlewareResolverTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function the_middleware_resolver_can_be_constructed_from_cache(): void
    {
        $blueprint1 = MiddlewareBlueprint::from(FooMiddleware::class, ['bar', 'baz']);
        $blueprint2 = MiddlewareBlueprint::from(BarMiddleware::class, ['biz']);

        $route_cache = [
            'r1' => [$blueprint1->asArray(), $blueprint2->asArray()],
        ];

        $resolver = MiddlewareResolver::fromCache($route_cache, [
            RoutingConfigurator::GLOBAL_MIDDLEWARE => [$blueprint1->asArray()],
            RoutingConfigurator::FRONTEND_MIDDLEWARE => [$blueprint2->asArray()],
        ]);

        $route = Route::create('/', Route::DELEGATE, 'r1');
        $route->middleware(BazMiddleware::class);

        $controller_action = new ControllerAction(Route::DELEGATE, $this->psr_container);

        // BazMiddleware is not present since It's loaded from cache.
        $this->assertEquals([$blueprint1, $blueprint2], $resolver->resolveForRoute($route, $controller_action));

        $this->assertEquals(
            [$blueprint1, $blueprint2],
            $resolver->resolveForRequestWithoutRoute($this->frontendRequest())
        );

        $this->assertEquals([$blueprint1], $resolver->resolveForRequestWithoutRoute($this->adminRequest('/foo')));

        $this->assertEquals([$blueprint1], $resolver->resolveForRequestWithoutRoute($this->adminRequest('/foo')));
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
            RoutingConfigurator::API_MIDDLEWARE => [$blueprint2->asArray()],
        ]);

        $this->assertEquals(
            [$blueprint1, $blueprint2],
            $resolver->resolveForRequestWithoutRoute($this->apiRequest())
        );

        $this->assertEquals([$blueprint1], $resolver->resolveForRequestWithoutRoute($this->adminRequest('/foo')));

        $this->assertEquals(
            [$blueprint1],
            $resolver->resolveForRequestWithoutRoute($this->frontendRequest('/foo'))
        );
    }

    /**
     * @test
     */
    public function test_exception_if_a_route_is_not_in_cache(): void
    {
        $blueprint1 = MiddlewareBlueprint::from(FooMiddleware::class, ['bar', 'baz']);
        $blueprint2 = MiddlewareBlueprint::from(BarMiddleware::class, ['biz']);

        $route_cache = [
            'r2' => [$blueprint1->asArray(), $blueprint2->asArray()],
        ];

        $resolver = MiddlewareResolver::fromCache($route_cache, [
            RoutingConfigurator::GLOBAL_MIDDLEWARE => [$blueprint1->asArray()],
            RoutingConfigurator::FRONTEND_MIDDLEWARE => [$blueprint2->asArray()],
        ]);

        $route = Route::create('/', Route::DELEGATE, 'r1');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('The middleware resolver is cached but has no entry for route [r1].');
        $resolver->resolveForRoute($route, new ControllerAction(Route::DELEGATE, $this->psr_container));
    }

    /**
     * @test
     */
    public function test_create_middleware_cache(): void
    {
        $route1 = Route::create('/foo', Route::DELEGATE, 'r1')->middleware('group1');
        $route2 = Route::create('/bar', Route::DELEGATE, 'r2')->middleware('foo:FOO');
        $route3 = Route::create('/baz', ControllerWithBarMiddleware::class, 'r3')->middleware(['foo:FOO']);

        $routes = new RouteCollection([$route1, $route2, $route3]);

        $resolver = new MiddlewareResolver(
            [RoutingConfigurator::FRONTEND_MIDDLEWARE],
            [
                'foo' => FooMiddleware::class,
                'bar' => BarMiddleware::class,
                'baz' => BazMiddleware::class,
            ],
            [
                'group1' => ['foo', 'bar'],
                'frontend' => ['baz', 'bar'],
            ],
            [BarMiddleware::class]
        );

        $pimple = new Container();
        $psr = new \Pimple\Psr11\Container($pimple);

        $cache = $resolver->createMiddlewareCache($routes, $psr);

        $this->assertTrue(isset($cache['route_map']));
        $this->assertTrue(isset($cache['route_map']));
        $this->assertTrue(isset($cache['route_map']['r1']));
        $this->assertTrue(isset($cache['route_map']['r2']));
        $this->assertTrue(isset($cache['route_map']['r3']));

        $this->assertTrue(isset($cache['request_map']));
        $this->assertTrue(isset($cache['request_map']['frontend']));
        $this->assertTrue(isset($cache['request_map']['api']));
        $this->assertTrue(isset($cache['request_map']['admin']));
        $this->assertTrue(isset($cache['request_map']['global']));

        $this->assertSame([
            [
                'class' => BarMiddleware::class,
                'args' => [],
            ],
            [
                'class' => FooMiddleware::class,
                'args' => [],
            ],
        ], $cache['route_map']['r1']);

        $this->assertSame([
            [
                'class' => FooMiddleware::class,
                'args' => ['FOO'],
            ],
        ], $cache['route_map']['r2']);

        $this->assertSame([
            [
                'class' => BarMiddleware::class,
                'args' => [],
            ],
            [
                'class' => FooMiddleware::class,
                'args' => ['FOO'],
            ],
        ], $cache['route_map']['r3']);

        $this->assertSame([], $cache['request_map']['global']);
        $this->assertSame([], $cache['request_map']['api']);
        $this->assertSame([], $cache['request_map']['admin']);

        // Bar has higher priority
        $this->assertSame([
            [
                'class' => BarMiddleware::class,
                'args' => [],
            ],
            [
                'class' => BazMiddleware::class,
                'args' => [],
            ],
        ], $cache['request_map']['frontend']);
    }

    /**
     * @test
     */
    public function creating_the_middleware_cache_takes_settings_for_always_run_into_account(): void
    {
        $resolver = new MiddlewareResolver(
            [RoutingConfigurator::FRONTEND_MIDDLEWARE],
            [
                'foo' => FooMiddleware::class,
                'bar' => BarMiddleware::class,
                'baz' => BazMiddleware::class,
            ],
            [
                'frontend' => ['foo'],
                'admin' => ['bar'],
                'api' => ['baz'],
                'global' => ['foo', 'bar'],
            ],
            [BarMiddleware::class]
        );

        $pimple = new Container();
        $psr = new \Pimple\Psr11\Container($pimple);

        $cache = $resolver->createMiddlewareCache(new RouteCollection(), $psr);

        $request_map = $cache['request_map'];

        $this->assertTrue(isset($request_map['frontend']), 'Frontend middleware should be in request_map cache');
        $this->assertTrue(isset($request_map['api']), 'API middleware should have been in request_map cache');
        $this->assertTrue(isset($request_map['admin']), 'Admin middleware should have been in request_map cache');
        $this->assertTrue(isset($request_map['global']), 'Global middleware should have been in request_map cache');

        $this->assertSame([
            [
                'class' => FooMiddleware::class,
                'args' => [],
            ],
        ], $request_map['frontend']);

        $this->assertSame([], $request_map['api']);
        $this->assertSame([], $request_map['admin']);
        $this->assertSame([], $request_map['global']);

        $resolver = MiddlewareResolver::fromCache($cache['route_map'], $cache['request_map']);

        $this->assertEquals([
            new MiddlewareBlueprint(FooMiddleware::class, []),
        ], $resolver->resolveForRequestWithoutRoute($this->frontendRequest()));

        $this->assertEquals([], $resolver->resolveForRequestWithoutRoute($this->adminRequest('/wp-admin')));
        $this->assertEquals([], $resolver->resolveForRequestWithoutRoute($this->apiRequest()));
    }

    /**
     * @test
     */
    public function middleware_caching_detects_recursion(): void
    {
        $this->expectException(MiddlewareRecursion::class);
        $this->expectExceptionMessage('Detected middleware recursion: global->group2->group3->group2');

        new MiddlewareResolver(
            [],
            [],
            [
                'correct_1' => [FooMiddleware::class],
                'correct_2' => [BarMiddleware::class],
                RoutingConfigurator::GLOBAL_MIDDLEWARE => ['correct_1', 'group2'],
                'group2' => ['correct_2', 'group3'],
                'group3' => [BazMiddleware::class, 'group2'],
            ],
            []
        );
    }
}
