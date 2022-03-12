<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class TrailingSlashesTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function routes_can_be_defined_without_leading_slash(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo_route', 'foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('static', $request);
    }

    /**
     * @test
     */
    public function routes_can_be_defined_with_leading_slash(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo_route', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('static', $request);
    }

    /**
     * @test
     */
    public function a_route_with_trailing_slash_does_not_match_a_path_without_trailing_slash(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo_route', '/foo/', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/foo/');
        $this->assertResponseBody('static', $request);
    }

    /**
     * @test
     */
    public function a_route_without_trailing_slash_does_not_match_a_path_with_trailing_slash(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo_route', '/foo', RoutingTestController::class);
        });

        $request = $this->frontendRequest('/foo/');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/foo');
        $this->assertResponseBody('static', $request);
    }

    /**
     * @test
     */
    public function test_required_route_segments_and_trailing_slashes(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/route1/{param1}/{param2}', [RoutingTestController::class, 'twoParams']);
            $configurator->get('route2', '/route2/{param1}/{param2}/', [RoutingTestController::class, 'twoParams']);
        });

        $request = $this->frontendRequest('/route1/foo/bar/');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/route1/foo/bar');
        $this->assertResponseBody('foo:bar', $request);

        $request = $this->frontendRequest('/route2/foo/bar');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/route2/foo/bar/');
        $this->assertResponseBody('foo:bar', $request);
    }

    /**
     * @test
     */
    public function test_optional_route_segments_and_trailing_slashes(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get(
                'route1',
                '/notrailing/{param1?}/{param2?}',
                [RoutingTestController::class, 'twoOptional']
            );
            $configurator->get(
                'route2',
                '/trailing/{param1?}/{param2?}/',
                [RoutingTestController::class, 'twoOptional']
            );
        });

        // Only with trailing
        $request = $this->frontendRequest('/trailing/foo');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/trailing/foo/');
        $this->assertResponseBody('foo:default2', $request);

        $request = $this->frontendRequest('/trailing/foo/bar');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/trailing/foo/bar/');
        $this->assertResponseBody('foo:bar', $request);

        $request = $this->frontendRequest('/notrailing/foo');
        $this->assertResponseBody('foo:default2', $request);

        $request = $this->frontendRequest('/notrailing/foo/');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/notrailing/foo/bar');
        $this->assertResponseBody('foo:bar', $request);

        $request = $this->frontendRequest('/notrailing/foo/bar/');
        $this->assertResponseBody('', $request);
    }

    /**
     * @test
     */
    public function test_with_only_one_optional_segment(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('route1', '/no_trailing/{param1?}', [RoutingTestController::class, 'dynamic']);
            $configurator->get('route2', '/trailing/{param1?}/', [RoutingTestController::class, 'dynamic']);
        });

        // Only with trailing
        $request = $this->frontendRequest('/trailing/foo');
        $this->assertResponseBody('', $request);

        $request = $this->frontendRequest('/trailing/foo/');
        $this->assertResponseBody('dynamic:foo', $request);

        // No trailing
        $request = $this->frontendRequest('/no_trailing/foo');
        $this->assertResponseBody('dynamic:foo', $request);

        $request = $this->frontendRequest('/no_trailing/foo/');
        $this->assertResponseBody('', $request);
    }
}
