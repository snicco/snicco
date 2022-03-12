<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Exception\MethodNotAllowed;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class RouteAttributesTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function basic_get_routing_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function non_allowed_methods_throw_a_405_exception(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
            $configurator->get('route2', '/foo/{bar}', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo', [], 'POST');

        try {
            $this->runNewPipeline($request);
            $this->fail('Expected exception.');
        } catch (MethodNotAllowed $e) {
            $this->assertStringContainsString('/foo', $e->getMessage());
        }

        $request = $this->frontendRequest('/foo/bar', [], 'POST');

        try {
            $this->runNewPipeline($request);
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
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
            $configurator->get('bar', '/foo/{param}', [RoutingTestController::class, 'dynamic']);
        });

        $request = $this->frontendRequest('/foo', [], 'GET');
        $response = $this->runNewPipeline($request);
        $response->assertOk()
            ->assertBodyExact(RoutingTestController::static);

        $request = $this->frontendRequest('/foo', [], 'HEAD');
        $response = $this->runNewPipeline($request);
        $response->assertOk()
            ->assertNotDelegated();

        $request = $this->frontendRequest('/foo/bar', [], 'GET');
        $response = $this->runNewPipeline($request);
        $response->assertOk()
            ->assertBodyExact('dynamic:bar');

        $request = $this->frontendRequest('/foo/bar', [], 'HEAD');
        $response = $this->runNewPipeline($request);
        $response->assertOk()
            ->assertNotDelegated();
    }

    /**
     * @test
     */
    public function basic_post_routing_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->post('foo', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_put_routing_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->put('foo', '/foo', RoutingTestController::class);
        });
        $request = $this->frontendRequest('/foo', [], 'PUT');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_patch_routing_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->patch('foo', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo', [], 'PATCH');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_delete_routing_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->delete('foo', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo', [], 'DELETE');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function basic_options_routing_works(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->options('foo', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo', [], 'OPTIONS');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function a_route_can_match_all_methods(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->any('foo', '/foo', RoutingTestController::class);
        });

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
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->match(['GET', 'POST'], 'foo', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo', [], 'GET');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('/foo', [], 'PUT');
        $this->expectException(MethodNotAllowed::class);
        $this->runNewPipeline($request);
    }

    /**
     * @test
     */
    public function static_and_dynamic_routes_can_be_added_for_the_same_uri_while_static_routes_take_precedence(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('static', '/foo/baz', [RoutingTestController::class, 'static']);
            $configurator->get('dynamic', '/foo/{dynamic}', [RoutingTestController::class, 'dynamic']);
        });

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
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class)
                ->middleware('foo');
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':foo_middleware', $request);
    }

    /**
     * @test
     */
    public function a_route_can_have_multiple_middlewares(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class)
                ->middleware(['foo', 'bar']);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':bar_middleware:foo_middleware', $request);
    }

    /**
     * @test
     */
    public function middleware_can_pass_arguments(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class)
                ->middleware(['foo:FOO', 'bar:BAR']);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':BAR:FOO', $request);
    }

    /**
     * @test
     */
    public function a_route_can_be_set_to_not_handle_anything_but_only_run_middleware(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo')
                ->middleware(FooMiddleware::class);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody(':foo_middleware', $request);
    }

    /**
     * @test
     */
    public function a_route_with_the_same_static_url_cant_be_added_twice(): void
    {
        $this->expectException(BadRouteConfiguration::class);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/foo', RoutingTestController::class);
            $configurator->get('route2', '/foo', RoutingTestController::class);
        });
    }

    /**
     * @test
     */
    public function a_dynamic_route_cant_shadow_a_static_route(): void
    {
        $this->expectException(BadRouteConfiguration::class);

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/{foo}', RoutingTestController::class);
            $configurator->get('route2', '/foo', RoutingTestController::class);
        });
    }

    /**
     * @test
     */
    public function a_route_with_the_same_name_can_be_added_twice_even_if_urls_are_different(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/foo', RoutingTestController::class);
            $configurator->get('route1', '/bar', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertEmptyBody($request);

        $request = $this->frontendRequest('/bar');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }
}
