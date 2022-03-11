<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use LogicException;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class FallbackRouteTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function users_can_create_a_custom_fallback_web_route(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback']);
        });

        $request = $this->frontendRequest('/bar');
        $this->assertResponseBody('fallback:bar', $request);

        $request = $this->frontendRequest('/bar/baz');
        $this->assertResponseBody('fallback:bar/baz', $request);
    }

    /**
     * @test
     */
    public function throws_an_exception_if_a_route_is_created_after_the_fallback_route(): void
    {
        $this->expectExceptionMessage(LogicException::class);
        $this->expectExceptionMessage('Route [route1] was registered after a fallback route was defined.');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback']);
            $configurator->get('route1', '/foo');
        });
    }

    /**
     * @test
     */
    public function the_fallback_route_does_not_match_admin_requests(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback(RoutingTestController::class);
        });

        $response = $this->runNewPipeline($this->adminRequest('/wp-admin/admin.php?page=foo'));
        $response->assertDelegated();
    }

    /**
     * @test
     */
    public function the_fallback_route_will_not_match_for_requests_that_are_specified_in_the_exclusion_list(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback']);
        });

        $this->assertResponseBody('fallback:foo.bar', $this->frontendRequest('/foo.bar'));

        // These are excluded by default
        $this->assertEmptyBody($this->frontendRequest('/favicon.ico'));
        $this->assertEmptyBody($this->frontendRequest('/robots.txt'));
        $this->assertEmptyBody($this->frontendRequest('/sitemap.xml'));
    }

    /**
     * @test
     */
    public function custom_exclusions_words_can_be_specified(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback'], ['foo', 'bar']);
        });

        $this->assertResponseBody('', $this->frontendRequest('/foobar'));
        $this->assertResponseBody('', $this->frontendRequest('/foo'));
        $this->assertResponseBody('', $this->frontendRequest('/bar'));

        $this->assertResponseBody('fallback:baz', $this->frontendRequest('/baz'));
        $this->assertResponseBody('fallback:robots.txt', $this->frontendRequest('/robots.txt'));
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function an_exception_is_thrown_for_non_string_exclusions(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('All fallback excludes have to be strings.');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback'], ['foo', 1]);
        });
    }

    /**
     * @test
     */
    public function the_pipe_symbol_can_be_passed(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback'], ['foo|bar', 'baz']);
        });

        $this->assertResponseBody('', $this->frontendRequest('/foo'));
        $this->assertResponseBody('', $this->frontendRequest('/bar'));
        $this->assertResponseBody('', $this->frontendRequest('/baz'));

        $this->assertResponseBody('fallback:biz', $this->frontendRequest('/biz'));
    }

    /**
     * @test
     */
    public function fallback_routes_dont_match_requests_starting_with_wp_admin(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback']);
        });

        $this->runNewPipeline($this->frontendRequest('/wp-admin/foo'))
            ->assertDelegated();
    }

    /**
     * @test
     */
    public function the_fallback_route_will_match_trailing_slashes(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->fallback([RoutingTestController::class, 'fallback']);
        });

        $request = $this->frontendRequest('/bar/');
        $this->assertResponseBody('fallback:bar/', $request);

        $request = $this->frontendRequest('/bar/baz/');
        $this->assertResponseBody('fallback:bar/baz/', $request);
    }
}
