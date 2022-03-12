<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Admin;

use LogicException;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class AdminRoutesTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function an_exception_is_thrown_for_admin_routes_that_declare_patterns(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage("Admin routes can not define route parameters.\nViolating route [admin1].");

        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin1', 'admin.php/foo/{bar}');
        });
        $routing->urlMatcher();
    }

    /**
     * @test
     */
    public function test_exception_for_admin_routes_that_use_the_admin_dashboard_prefix(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage('You should not add the prefix [/wp-admin] to the admin route [admin1]');

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin1', '/wp-admin/admin.php/foo/');
        });
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_admin_routes_are_registered_with_pending_attributes_without_a_call_to_group(
        ): void {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('group');

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->middleware('foo')
                ->page('admin.1', 'admin.php/foo', Route::DELEGATE, [], null);
        });
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_admin_routes_are_registered_with_the_prefix_declared_explicitly(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('You should not add the prefix [/wp-admin] to the admin route [admin.1]');

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin.1', '/wp-admin/admin.php/foo', Route::DELEGATE, [], null);
        });
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_an_admin_route_is_registered_without_using_the_admin_method(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage(
            sprintf(
                'You tried to register the route [r1] that goes to the admin dashboard without using the dedicated admin() method on an instance of [%s]',
                AdminRoutingConfigurator::class
            )
        );

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('r1', '/wp-admin/admin.php/foo', RoutingTestController::class);
        });
    }

    /**
     * @test
     */
    public function routes_in_an_admin_group_match_without_needing_to_specify_the_full_path(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'admin.php/foo', RoutingTestController::class, [], null);
        });

        $request = $this->adminRequest('/wp-admin/admin.php?page=foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function routes_to_different_admin_pages_dont_match(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'options.php/foo', RoutingTestController::class);
        });

        $request = $this->adminRequest('/wp-admin/admin.php?page=bar');
        $this->assertResponseBody('', $request);
    }

    /**
     * @test
     */
    public function non_get_requests_do_not_match(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'admin.php/foo', RoutingTestController::class);
        });

        $request = $this->adminRequest('/wp-admin/admin.php?page=foo')
            ->withMethod('POST');

        $response = $this->runNewPipeline($request);
        $response->assertDelegated();
    }

    /**
     * @test
     */
    public function two_different_admin_routes_can_be_created(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'admin.php/foo', RoutingTestController::class,);

            $configurator->page('r2', 'admin.php/bar', RoutingTestController::class,);
        });

        $request = $this->adminRequest('/wp-admin/admin.php?page=foo');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->adminRequest('/wp-admin/admin.php?page=bar');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->adminRequest('/wp-admin/admin.php?page=baz');
        $this->assertResponseBody('', $request);
    }

    /**
     * @test
     */
    public function reverse_routing_works_with_admin_routes(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'admin.php/foo', RoutingTestController::class,);
        });

        $url = $routing->urlGenerator()
            ->toRoute('r1', [
                'bar' => 'baz',
            ]);
        $this->assertSame('/wp-admin/admin.php?bar=baz&page=foo', $url);
    }

    /**
     * @test
     */
    public function a_route_with_the_same_page_query_var_but_different_parent_menu_doesnt_match(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', '/admin.php/foo', RoutingTestController::class,);
        });

        $request = $this->adminRequest('/wp-admin/admin.php?page=foo');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->adminRequest('/wp-admin/tools.php?page=foo');
        $this->assertResponseBody('', $request);
    }

    /**
     * @test
     */
    public function admin_routes_do_not_match_for_non_admin_requests_that_have_the_same_rewritten_url_but_are_not_loaded_from_withing_the_admin_dashboard(
        ): void {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'options.php/foo', RoutingTestController::class, [],);
        });

        $request = $this->frontendRequest('/wp-admin/options.php/foo');
        $this->runNewPipeline($request)
            ->assertDelegated();

        $request = $this->adminRequest('/wp-admin/options.php?page=foo');
        $this->assertResponseBody(RoutingTestController::static, $request);
    }

    /**
     * @test
     */
    public function the_real_request_path_is_available_in_the_controller_not_the_rewritten_one(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('r1', 'admin.php/foo', [RoutingTestController::class, 'returnFullRequest'],);
        });

        $request = $this->adminRequest('/wp-admin/admin.php?page=foo');
        $as_string = (string) $request->getUri();
        $this->assertStringContainsString('page=foo', $as_string);
        $this->assertResponseBody($as_string, $request);
    }
}
