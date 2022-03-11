<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\UrlMatcher;

use LogicException;
use Snicco\Component\HttpRouting\Routing\Exception\RouteNotFound;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @internal
 */
final class RouteGroupsTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function an_exception_is_thrown_if_a_route_is_added_and_delegated_attributes_have_not_been_applied(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cant register route [r1] because delegated');

        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->prefix('foo')
                ->get('r1', '/bar', RoutingTestController::class);
        });
    }

    /**
     * @test
     */
    public function middleware_is_merged_for_route_groups(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->middleware('foo:FOO')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router
                        ->get('r1', '/foo', RoutingTestController::class)
                        ->middleware('bar:BAR');

                    $router
                        ->post('r2', '/foo', RoutingTestController::class);
                });
        });

        $get_request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':BAR:FOO', $get_request);

        $post_request = $this->frontendRequest('/foo', [], 'POST');
        $this->assertResponseBody(RoutingTestController::static . ':FOO', $post_request);
    }

    /**
     * @test
     */
    public function the_group_namespace_is_applied_to_child_routes(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->namespace(self::CONTROLLER_NAMESPACE)
                ->group(function (WebRoutingConfigurator $router): void {
                    $router->get('r1', '/foo', 'RoutingTestController');
                });
        });

        $get_request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $get_request);
    }

    /**
     * @test
     */
    public function a_group_can_prefix_all_child_route_urls(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->prefix('foo')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router->get('r1', '/bar', RoutingTestController::class);
                    $router->get('r2', '/baz', RoutingTestController::class);
                });
        });

        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('/foo/bar'));
        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('/foo/baz'));
        $this->assertEmptyBody($this->frontendRequest('/foo'));

        $this->assertSame('/foo/bar', $routing->urlGenerator()->toRoute('r1'));
    }

    /**
     * @test
     */
    public function a_group_name_can_be_applied_to_child_routes(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->name('users')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router->get('route1', '/bar', RoutingTestController::class);
                    $router->get('route2', '/baz', RoutingTestController::class);
                });
        });

        $this->assertSame('/bar', $routing->urlGenerator()->toRoute('users.route1'));
        $this->assertSame('/baz', $routing->urlGenerator()->toRoute('users.route2'));

        $this->expectException(RouteNotFound::class);
        $routing->urlGenerator()
            ->toRoute('route1');
    }

    /**
     * @test
     */
    public function the_namespace_is_always_overwritten_by_child_routes(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->namespace('Tests\FalseNamespace')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router
                        ->namespace(self::CONTROLLER_NAMESPACE)->group(
                            function (WebRoutingConfigurator $router): void {
                                $router->get('r1', '/foo', 'RoutingTestController');
                            }
                        );
                });
        });

        $get_request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static, $get_request);
    }

    /**
     * @test
     */
    public function group_prefixes_are_merged_on_multiple_levels(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->prefix('foo')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router
                        ->prefix('bar')
                        ->group(function (WebRoutingConfigurator $router): void {
                            $router->get('r1', '/baz', RoutingTestController::class);
                            $router->get('r2', '/biz', RoutingTestController::class);
                        });
                });
        });

        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('/foo/bar/baz'));

        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('/foo/bar/biz'));

        $this->assertEmptyBody($this->frontendRequest('/baz'));
        $this->assertEmptyBody($this->frontendRequest('/biz'));
        $this->assertEmptyBody($this->frontendRequest('/bar/baz'));
        $this->assertEmptyBody($this->frontendRequest('/bar/biz'));
    }

    /**
     * @test
     */
    public function group_names_are_merged_on_multiple_levels(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->name('users')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router->name('admins')
                        ->group(function (WebRoutingConfigurator $router): void {
                            $router->get('calvin', '/bar', RoutingTestController::class);
                            $router->get('marlon', '/baz', RoutingTestController::class);
                        });

                    $router->get('jon', '/jon', RoutingTestController::class);
                });
        });

        $this->assertSame('/bar', $routing->urlGenerator()->toRoute('users.admins.calvin'));
        $this->assertSame('/baz', $routing->urlGenerator()->toRoute('users.admins.marlon'));
        $this->assertSame('/jon', $routing->urlGenerator()->toRoute('users.jon'));

        $this->expectException(RouteNotFound::class);
        $routing->urlGenerator()
            ->toRoute('admins.calvin');
    }

    /**
     * @test
     */
    public function middleware_is_merged_on_multiple_levels(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->middleware('foo:FOO')
                ->group(function (WebRoutingConfigurator $router): void {
                    $router->middleware('bar:BAR')
                        ->group(function (WebRoutingConfigurator $router): void {
                            $router
                                ->get('r1', '/foo', RoutingTestController::class)
                                ->middleware('baz');
                        });
                });
        });

        $get_request = $this->frontendRequest('/foo');
        $this->assertResponseBody(RoutingTestController::static . ':baz_middleware:BAR:FOO', $get_request);
    }
}
