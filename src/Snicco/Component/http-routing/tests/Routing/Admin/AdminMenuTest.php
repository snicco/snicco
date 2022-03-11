<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Admin;

use LogicException;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\Exception\BadRouteConfiguration;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\BarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\fixtures\FoobarMiddleware;
use Snicco\Component\HttpRouting\Tests\fixtures\FooMiddleware;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

/**
 * @psalm-suppress PossiblyUndefinedIntArrayOffset
 *
 * @internal
 */
final class AdminMenuTest extends HttpRunnerTestCase
{
    /**
     * @test
     */
    public function an_admin_menu_will_not_be_configured_if_no_route_handler_is_defined(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin1', '/foo', Route::DELEGATE, [], null);
        });

        $items = $routing->adminMenu()
            ->items();

        $this->assertCount(0, $items);
    }

    /**
     * @test
     */
    public function an_exception_is_thrown_if_admin_menu_items_are_passed_and_no_handler_is_defined(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage('Route [admin2] can not have an admin menu item without an action.');

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page(
                'admin2',
                '/bar',
                Route::DELEGATE,
                [
                    AdminMenuItem::MENU_TITLE => 'My explicit menu title',
                ],
            );
        });
    }

    /**
     * @test
     */
    public function an_admin_menu_will_be_preconfigured_based_on_the_route(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin.my_route', 'admin.php/foo', RoutingTestController::class,);

            $configurator->page('admin.my.second_route', 'admin.php/bar', RoutingTestController::class,);
        });

        $items = $routing->adminMenu()
            ->items();

        $this->assertCount(2, $items);

        $first = $items[0];

        $this->assertSame('My Route', $first->menuTitle());
        $this->assertSame('My Route', $first->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $first->slug()->asString());
        $this->assertNull($first->icon());
        $this->assertNull($first->requiredCapability());
        $this->assertNull($first->icon());

        $second = $items[1];

        $this->assertSame('Second Route', $second->menuTitle());
        $this->assertSame('Second Route', $second->pageTitle());
        $this->assertSame('/wp-admin/admin.php/bar', $second->slug()->asString());
        $this->assertNull($second->icon());
        $this->assertNull($second->requiredCapability());
        $this->assertNull($second->icon());
    }

    /**
     * @test
     */
    public function arguments_can_be_passed_explicitly_and_will_be_merged(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page(
                'admin.my_route',
                'admin.php/foo',
                RoutingTestController::class,
                [
                    AdminMenuItem::MENU_TITLE => 'My explicit menu title',
                ],
            );
        });

        $item = $routing->adminMenu()
            ->items();

        $first = $item[0];
        $this->assertSame('My explicit menu title', $first->menuTitle());
        $this->assertSame('My explicit menu title', $first->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $first->slug()->asString());
        $this->assertNull($first->icon());
        $this->assertNull($first->requiredCapability());
        $this->assertNull($first->icon());
    }

    /**
     * @test
     */
    public function admin_menus_will_not_be_added_if_null_is_passed_explicitly_even_tho_a_controller_is_defined(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin.redirect', 'admin.php/foo', RoutingTestController::class, null,);
        });

        $item = $routing->adminMenu()
            ->items();

        $this->assertCount(0, $item);
    }

    /**
     * @test
     */
    public function sub_menu_items_can_be_added_by_passing_in_another_route(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $route1 = $configurator->page('admin_parent', '/admin.php/parent', RoutingTestController::class,);

            $configurator->page(
                'admin_sub',
                '/admin.php/sub',
                RoutingTestController::class,
                [
                    AdminMenuItem::MENU_TITLE => 'My explicit menu title',
                ],
                $route1,
            );
        });

        $items = $routing->adminMenu()
            ->items();

        $this->assertCount(2, $items);

        $this->assertSame('/wp-admin/admin.php/parent', $items[1]->parentSlug()->asString());

        $this->expectException(LogicException::class);
        $items[0]->parentSlug();
    }

    /**
     * @test
     */
    public function sub_menu_items_can_be_added_in_a_parent_scope_closure(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $parent = $configurator->page('admin_parent', '/admin.php/parent', RoutingTestController::class,);

            $configurator->subPages($parent, function (AdminRoutingConfigurator $configurator): void {
                $configurator->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
                $configurator->page('admin_sub_2', '/admin.php/sub2', RoutingTestController::class);
            });
        });

        $items = $routing->adminMenu()
            ->items();

        $this->assertCount(3, $items);

        $this->assertSame('/wp-admin/admin.php/parent', $items[1]->parentSlug()->asString());
        $this->assertSame('/wp-admin/admin.php/parent', $items[2]->parentSlug()->asString());

        $this->expectException(LogicException::class);
        $items[0]->parentSlug();
    }

    /**
     * @test
     */
    public function test_exception_for_calling_recursive_subpages(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage('Nested calls');

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page('admin_parent', '/admin.php/parent', RoutingTestController::class, [],);
            $configurator->subPages($r1, function (AdminRoutingConfigurator $configurator): void {
                $r2 = $configurator->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);

                $configurator->subPages($r2, function (): void {
                });
            });
        });
    }

    /**
     * @test
     */
    public function test_exception_for_passing_a_route_inside_sub_pages(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage(
            'You can not pass route/parent_slug [admin_sub_1] as the last argument during a call to subPages().'
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page('admin_parent', '/admin.php/parent', RoutingTestController::class, [],);

            $configurator->subPages($r1, function (AdminRoutingConfigurator $configurator): void {
                $r2 = $configurator->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
                $configurator->page('admin_sub_2', '/admin.php/sub2', RoutingTestController::class, [], $r2);
            });
        });
    }

    /**
     * @test
     */
    public function test_routing_to_subpages_works(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page('admin_parent', '/admin.php/parent', RoutingTestController::class);
            $configurator->subPages($r1, function (AdminRoutingConfigurator $router): void {
                $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
            });
        });

        $this->runNewPipeline($this->adminRequest('/wp-admin/admin.php?page=sub1'))
            ->assertOk()
            ->assertSeeText(RoutingTestController::static);
    }

    /**
     * @test
     */
    public function calling_sub_pages_will_apply_all_middleware_of_the_parent_group(): void
    {
        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page(
                'admin_parent',
                '/admin.php/parent',
                RoutingTestController::class,
                [],
            )->middleware(FooMiddleware::class);

            $configurator->subPages($r1, function (AdminRoutingConfigurator $configurator): void {
                $configurator->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
            });
        });

        $this->runNewPipeline($this->adminRequest('/wp-admin/admin.php?page=sub1'))
            ->assertOk()
            ->assertSeeText(RoutingTestController::static . ':foo_middleware');
    }

    /**
     * @test
     */
    public function it_works_with_nested_grouping_and_sup_pages(): void
    {
        $this->withMiddlewareGroups([
            'admin' => [FoobarMiddleware::class],
        ]);

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->group(function (AdminRoutingConfigurator $router): void {
                $r1 = $router->page(
                    'admin_parent',
                    '/admin.php/parent',
                    RoutingTestController::class,
                    [],
                )->middleware(FooMiddleware::class);

                $router->subPages($r1, function (AdminRoutingConfigurator $router): void {
                    $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class)
                        ->middleware(BarMiddleware::class);
                });
            }, [
                RoutingConfigurator::MIDDLEWARE_KEY => [RoutingConfigurator::ADMIN_MIDDLEWARE],
            ]);
        });

        $this->runNewPipeline($this->adminRequest('/wp-admin/admin.php?page=sub1'))
            ->assertOk()
            ->assertSeeText(RoutingTestController::static . ':bar_middleware:foo_middleware:foobar_middleware');
    }

    /**
     * @test
     */
    public function parent_pages_can_be_added_by_slug_only(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page(
                'options.sub',
                '/options.php/sub',
                RoutingTestController::class,
                [
                    AdminMenuItem::MENU_TITLE => 'menu title',
                    AdminMenuItem::PAGE_TITLE => 'page title',
                ],
                'options.php'
            );
        });

        $items = $routing->adminMenu()
            ->items();

        $this->assertCount(1, $items);

        $item = $items[0];

        $this->assertSame('menu title', $item->menuTitle());
        $this->assertSame('page title', $item->pageTitle());
        $this->assertSame('/wp-admin/options.php', $item->parentSlug()->asString());
        $this->assertSame('/wp-admin/options.php/sub', $item->slug()->asString());
    }

    /**
     * @test
     */
    public function test_exception_if_parent_pages_with_slug_only_are_prefix_with_the_admin_area_prefix(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage(
            "You should not add the prefix [/wp-admin] to the parent slug of pages.\nAffected route [options.sub]"
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page(
                'options.sub',
                '/options.php/sub',
                RoutingTestController::class,
                [
                    AdminMenuItem::MENU_TITLE => 'menu title',
                    AdminMenuItem::PAGE_TITLE => 'page title',
                ],
                '/wp-admin/options.php'
            );
        });
    }

    /**
     * @test
     */
    public function test_exception_if_parent_is_used_that_has_no_menu_item(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage(
            'Can not use route [options.redirect] as a parent for [options.sub] because it has no menu item.'
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page('options.redirect', '/options.php',);

            $configurator->page('options.sub', '/options.php/sub', RoutingTestController::class, [], $r1);
        });
    }

    /**
     * @test
     */
    public function test_exception_for_parents_by_slug_if_path_is_incompatible(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage(
            'Route pattern [/wp-admin/admin.php/sub] is incompatible with parent slug [/wp-admin/options.php].'
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page(
                'options.sub',
                '/admin.php/sub',
                RoutingTestController::class,
                [
                    AdminMenuItem::MENU_TITLE => 'menu title',
                    AdminMenuItem::PAGE_TITLE => 'page title',
                ],
                'options.php'
            );
        });
    }

    /**
     * @test
     */
    public function test_exception_for_incompatible_path_with_parent_as_route(): void
    {
        $this->expectExceptionMessage(
            'Route pattern [/wp-admin/options.php/sub] is incompatible with parent slug [/wp-admin/admin.php/parent].'
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page('page1', '/admin.php/parent', RoutingTestController::class);
            $configurator->page('page2', '/options.php/sub', RoutingTestController::class, [], $r1);
        });
    }

    /**
     * @test
     */
    public function test_exception_if_a_subpage_is_used_as_a_parent_page(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage(
            'Can not use route [admin.sub1] as a parent for route [admin.sub2] because [admin.sub1] is already a child of parent slug [/wp-admin/admin.php/parent].'
        );

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $r1 = $configurator->page('admin.parent', '/admin.php/parent', RoutingTestController::class,);
            $r2 = $configurator->page('admin.sub1', '/admin.php/sub1', RoutingTestController::class, [], $r1);

            $configurator->page('admin.sub2', '/admin.php/sub2', RoutingTestController::class, [], $r2);
        });
    }

    /**
     * @test
     * @psalm-suppress InvalidScalarArgument
     */
    public function test_exception_if_parent_page_is_not_string_or_route(): void
    {
        $this->expectException(BadRouteConfiguration::class);
        $this->expectExceptionMessage('$parent has to be a string or an instance of Route.');

        $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('admin.sub1', '/admin.php/sub1', RoutingTestController::class, [], 1);
        });
    }

    /**
     * @test
     *
     * @psalm-suppress RedundantCondition
     */
    public function test_is_iterator(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('page1', 'admin.php/foo', RoutingTestController::class);
            $configurator->page('page2', 'admin.php/bar', RoutingTestController::class);
            $configurator->page('page3', 'admin.php/baz', RoutingTestController::class);
        });

        $count = 0;
        foreach ($routing->adminMenu()->items() as $key => $item) {
            $this->assertIsInt($key);
            $this->assertInstanceOf(AdminMenuItem::class, $item);
            ++$count;
        }

        $this->assertSame(3, $count);
    }
}
