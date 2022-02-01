<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use InvalidArgumentException;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Exception\MethodNotAllowed;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\GlobalMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

class RouteAttributesTest extends HttpRunnerTestCase
{

    /**
     * @test
     */
    public function exceptions_are_thrown_for_static_routes_that_shadow_each_other(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage('two routes');

        $this->routeConfigurator()->get('r1', '/foo');
        $this->routeConfigurator()->get('r2', '/foo');

        $this->runKernel($this->frontendRequest('/bogus'));
    }

    /**
     * @test
     */
    public function basic_get_routing_works(): void
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function non_allowed_methods_throw_a_405_exception(): void
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('route2', '/foo/{bar}', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'POST');
        try {
            $this->runKernel($request);
            $this->fail('Expected exception.');
        } catch (MethodNotAllowed $e) {
            $this->assertStringContainsString('/foo', $e->getMessage());
        }

        $request = $this->frontendRequest('/foo/bar', [], 'POST');
        try {
            $this->runKernel($request);
            $this->fail('Expected exception.');
        } catch (MethodNotAllowed $e) {
            $this->assertStringContainsString('/foo/bar', $e->getMessage());
        }
    }

    /**
     * @test
     */
    public function get_routes_match_head_requests(): void
    {
        $this->routeConfigurator()->get('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'HEAD');

        $response = $this->runKernel($request);
        $response->assertOk()->assertBodyExact('');
    }

    /**
     * @test
     */
    public function basic_post_routing_works(): void
    {
        $this->routeConfigurator()->post('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_put_routing_works(): void
    {
        $this->routeConfigurator()->put('foo', '/foo', RoutingTestController::class);
        $request = $this->frontendRequest('/foo', [], 'PUT');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_patch_routing_works(): void
    {
        $this->routeConfigurator()->patch('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'PATCH');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_delete_routing_works(): void
    {
        $this->routeConfigurator()->delete('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'DELETE');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_options_routing_works(): void
    {
        $this->routeConfigurator()->options('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'OPTIONS');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function a_route_can_match_all_methods(): void
    {
        $this->routeConfigurator()->any('foo', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo', [], 'GET');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'PUT');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'PATCH');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'DELETE');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'OPTIONS');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function a_route_can_match_specific_methods(): void
    {
        $this->routeConfigurator()->match(
            ['GET', 'POST'],
            'foo',
            '/foo',
            RoutingTestController::class
        );

        $request = $this->frontendRequest('/foo', [], 'GET');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'PUT');
        $this->expectException(MethodNotAllowed::class);
        $this->runKernel($request);
    }

    /**
     * @test
     */
    public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence(): void
    {
        $this->routeConfigurator()->get(
            'static',
            '/foo/baz',
            [RoutingTestController::class, 'static']
        );

        $this->routeConfigurator()->get(
            'dynamic',
            '/foo/{dynamic}',
            [RoutingTestController::class, 'dynamic']
        );

        $request = $this->frontendRequest('/foo/baz');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo/biz');
        $this->assertResponseBody(RoutingTestController::dynamic . ':biz', $request);
    }

    /**
     * @test
     */
    public function middleware_can_be_added_after_a_route_is_created(): void
    {
        $this->routeConfigurator()
            ->get('foo', '/foo', RoutingTestController::class)
            ->middleware('foo');

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':foo_middleware', $request);
    }

    /**
     * @test
     */
    public function a_route_can_have_multiple_middlewares(): void
    {
        $this->routeConfigurator()
            ->get('foo', '/foo', RoutingTestController::class)
            ->middleware(['foo', 'bar']);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(
            RoutingTestController::static . ':bar_middleware:foo_middleware',
            $request
        );
    }

    /**
     * @test
     */
    public function middleware_can_pass_arguments(): void
    {
        $this->routeConfigurator()
            ->get('foo', '/foo', RoutingTestController::class)
            ->middleware(['foo:FOO', 'bar:BAR']);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':BAR:FOO', $request);
    }

    /**
     * @test
     */
    public function a_route_can_be_set_to_not_handle_anything_but_only_run_middleware(): void
    {
        $GLOBALS['test'][GlobalMiddleware::run_times] = 0;

        $this->routeConfigurator()->get('foo', '/foo')
            ->middleware(GlobalMiddleware::class);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('', $request);

        $this->assertSame(1, $GLOBALS['test'][GlobalMiddleware::run_times]);
    }

    /**
     * @test
     */
    public function a_route_with_the_same_static_url_cant_be_added_twice(): void
    {
        $this->expectException(BadRouteConfiguration::class);

        $this->routeConfigurator()->get('route1', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('route2', '/foo', RoutingTestController::class);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('foo1', $request);
    }

    /**
     * @test
     */
    public function a_route_with_the_same_name_cant_be_added_twice_even_if_urls_are_different(): void
    {
        $this->routeConfigurator()->get('route1', '/foo', RoutingTestController::class);
        $this->routeConfigurator()->get('route1', '/bar', RoutingTestController::class);

        $request = $this->frontendRequest('/foo');
        $this->assertEmptyBody($request);

        $request = $this->frontendRequest('/bar');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function config_values_can_be_accessed(): void
    {
        $config = ['route_path' => '/foo'];

        $this->refreshRouter(null, null, $config);

        $this->routeConfigurator()->group(function (WebRoutingConfigurator $router) {
            $path = $router->configValue('route_path');

            $router->get('r1', $path, RoutingTestController::class);
        });

        $this->assertResponseBody(
            RoutingTestController::static,
            $this->frontendRequest('/foo')
        );
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_when_config_values_dont_exist(): void
    {
        $config = ['route_path' => '/foo'];

        $this->refreshRouter(null, null, $config);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bogus');

        $this->routeConfigurator()->group(function (WebRoutingConfigurator $router) {
            $path = $router->configValue('bogus');

            $router->get('r1', $path, RoutingTestController::class);
        });
    }

}


