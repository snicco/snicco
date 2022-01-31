<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use LogicException;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

class FallbackRouteTest extends HttpRunnerTestCase
{

    /** @test */
    public function users_can_create_a_custom_fallback_web_route()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);

        $request = $this->frontendRequest('/bar');
        $this->assertResponseBody('fallback:bar', $request);

        $request = $this->frontendRequest('/bar/baz');
        $this->assertResponseBody('fallback:bar/baz', $request);
    }

    /** @test */
    public function throws_an_exception_if_a_route_is_created_after_the_fallback_route()
    {
        $this->expectExceptionMessage(LogicException::class);
        $this->expectExceptionMessage(
            'Route [route1] was registered after a fallback route was defined.'
        );

        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);
        $this->routeConfigurator()->get('route1', '/foo');
    }

    /** @test */
    public function the_fallback_route_does_not_match_admin_requests()
    {
        $this->routeConfigurator()->fallback(RoutingTestController::class);

        $response = $this->runKernel($this->adminRequest('/wp-admin/admin.php?page=foo'));
        $response->assertDelegated();
    }

    /** @test */
    public function the_fallback_route_will_not_match_for_requests_that_are_specified_in_the_exclusion_list()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);

        $this->assertResponseBody(
            'fallback:foo.bar',
            $this->frontendRequest('/foo.bar')
        );

        // These are excluded by default
        $this->assertEmptyBody($this->frontendRequest('/favicon.ico'));
        $this->assertEmptyBody($this->frontendRequest('/robots.txt'));
        $this->assertEmptyBody($this->frontendRequest('/sitemap.xml'));
    }

    /** @test */
    public function custom_exclusions_words_can_be_specified()
    {
        $this->routeConfigurator()
            ->fallback([RoutingTestController::class, 'fallback'], ['foo', 'bar']);

        $this->assertResponseBody(
            '',
            $this->frontendRequest('/foobar')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('/foo')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('/bar')
        );

        $this->assertResponseBody('fallback:baz', $this->frontendRequest('/baz'));
        $this->assertResponseBody(
            'fallback:robots.txt',
            $this->frontendRequest('/robots.txt')
        );
    }

    /** @test */
    public function an_exception_is_thrown_for_non_string_exclusions()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('All fallback excludes have to be strings.');
        $this->routeConfigurator()
            ->fallback([RoutingTestController::class, 'fallback'], ['foo', 1]);
    }

    /** @test */
    public function the_pipe_symbol_can_be_passed()
    {
        $this->routeConfigurator()
            ->fallback([RoutingTestController::class, 'fallback'], ['foo|bar', 'baz']);

        $this->assertResponseBody(
            '',
            $this->frontendRequest('/foo')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('/bar')
        );
        $this->assertResponseBody(
            '',
            $this->frontendRequest('/baz')
        );

        $this->assertResponseBody('fallback:biz', $this->frontendRequest('/biz'));
    }

    /** @test */
    public function fallback_routes_dont_match_requests_starting_with_wp_admin()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);

        $this->runKernel($this->frontendRequest('/wp-admin/foo'))->assertDelegated();
    }

    /** @test */
    public function the_fallback_route_will_match_trailing_slashes()
    {
        $this->routeConfigurator()->fallback([RoutingTestController::class, 'fallback']);

        $request = $this->frontendRequest('/bar/');
        $this->assertResponseBody('fallback:bar/', $request);

        $request = $this->frontendRequest('/bar/baz/');
        $this->assertResponseBody('fallback:bar/baz/', $request);
    }

}