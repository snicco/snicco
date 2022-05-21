<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Admin;

use LogicException;
use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\Route\Route;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;

/**
 * @internal
 */
final class AdminMenuItemTest extends TestCase
{
    /**
     * @test
     */
    public function the_menu_item_can_be_constructed_just_with_a_route(): void
    {
        $route = Route::create('/wp-admin/admin.php/foo', RoutingTestController::class, 'admin.my_page');

        $item = AdminMenuItem::fromRoute($route);

        $this->assertSame('My Page', $item->menuTitle());
        $this->assertSame('My Page', $item->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $item->slug()->asString());
        $this->assertNull($item->requiredCapability());
        $this->assertNull($item->icon());
        $this->assertNull($item->position());

        $route = Route::create('/wp-admin/admin.php/foo', RoutingTestController::class, 'admin.my-page');

        $item = AdminMenuItem::fromRoute($route);

        $this->assertSame('My Page', $item->menuTitle());
        $this->assertSame('My Page', $item->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $item->slug()->asString());
        $this->assertNull($item->requiredCapability());
        $this->assertNull($item->icon());
        $this->assertNull($item->position());
    }

    /**
     * @test
     */
    public function extra_arguments_can_be_passed_and_while_take_precedence_over_attributes_inflected_from_the_route(
    ): void {
        $route = Route::create('/wp-admin/admin.php/foo', RoutingTestController::class, 'admin.my_page');

        // Only page  title
        $item = AdminMenuItem::fromRoute($route, [
            AdminMenuItem::PAGE_TITLE => 'My explicit page title',
        ]);

        $this->assertSame('My explicit page title', $item->menuTitle());
        $this->assertSame('My explicit page title', $item->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $item->slug()->asString());
        $this->assertNull($item->requiredCapability());
        $this->assertNull($item->icon());
        $this->assertNull($item->position());

        $route = Route::create('/wp-admin/admin.php/foo', RoutingTestController::class, 'admin.my_page');

        // menu and page title explicitly
        $item = AdminMenuItem::fromRoute(
            $route,
            [
                AdminMenuItem::PAGE_TITLE => 'My explicit page title',
                AdminMenuItem::MENU_TITLE => 'My explicit menu title',
            ]
        );

        $this->assertSame('My explicit menu title', $item->menuTitle());
        $this->assertSame('My explicit page title', $item->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $item->slug()->asString());

        // Only menu title
        $route = Route::create('/wp-admin/admin.php/foo', RoutingTestController::class, 'admin.my_page');

        $item = AdminMenuItem::fromRoute($route, [
            AdminMenuItem::MENU_TITLE => 'My explicit menu title',
        ]);

        $this->assertSame('My explicit menu title', $item->menuTitle());
        $this->assertSame('My explicit menu title', $item->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $item->slug()->asString());
    }

    /**
     * @test
     */
    public function test_empty_page_title_throws_exception(): void
    {
        $this->expectExceptionMessage('$page_title cant be empty');
        $route = $this->getRoute();
        AdminMenuItem::fromRoute($route, [
            AdminMenuItem::PAGE_TITLE => '',
        ]);
    }

    /**
     * @test
     */
    public function test_empty_menu_title_throws_exception(): void
    {
        $this->expectExceptionMessage('$menu_title cant be empty');
        $route = $this->getRoute();
        AdminMenuItem::fromRoute($route, [
            AdminMenuItem::MENU_TITLE => '',
            AdminMenuItem::PAGE_TITLE => 'foo',
        ]);
    }

    /**
     * @test
     */
    public function test_a_capability_can_be_set(): void
    {
        $route = $this->getRoute();
        $item = AdminMenuItem::fromRoute($route, [
            AdminMenuItem::CAPABILITY => 'read.whatever',
        ]);

        $this->assertSame('read.whatever', $item->requiredCapability());
    }

    /**
     * @test
     */
    public function test_empty_non_null_capability_throws(): void
    {
        $this->expectExceptionMessage('$capability has to be null or non empty string.');
        $route = $this->getRoute();
        AdminMenuItem::fromRoute($route, [
            AdminMenuItem::CAPABILITY => '',
        ]);
    }

    /**
     * @test
     */
    public function test_a_icon_can_be_set(): void
    {
        $route = $this->getRoute();
        $item = AdminMenuItem::fromRoute($route, [
            AdminMenuItem::ICON => 'admin-site-icon',
        ]);

        $this->assertSame('admin-site-icon', $item->icon());
    }

    /**
     * @test
     */
    public function test_empty_non_null_icon_throws(): void
    {
        $this->expectExceptionMessage('$icon has to be null or non empty string.');
        $route = $this->getRoute();
        AdminMenuItem::fromRoute($route, [
            AdminMenuItem::ICON => '',
        ]);
    }

    /**
     * @test
     */
    public function test_a_position_can_be_set(): void
    {
        $route = $this->getRoute();
        $item = AdminMenuItem::fromRoute($route, [
            AdminMenuItem::POSITION => 12,
        ]);

        $this->assertSame(12, $item->position());
    }

    /**
     * @test
     */
    public function test_empty_non_null_parent_throws(): void
    {
        $this->expectExceptionMessage('$parent_slug has to be null or non empty string.');
        $route = $this->getRoute();
        AdminMenuItem::fromRoute($route, [], '');
    }

    /**
     * @test
     */
    public function test_a_parent_slug_can_be_set(): void
    {
        $route = $this->getRoute();
        $item = AdminMenuItem::fromRoute($route, [], 'parent_slug');

        $this->assertSame('/parent_slug', $item->parentSlug()->asString());
    }

    /**
     * @test
     */
    public function test_is_child(): void
    {
        $route = $this->getRoute();
        $item = AdminMenuItem::fromRoute($route, [], 'parent_slug');
        $item2 = AdminMenuItem::fromRoute($route, []);

        $this->assertTrue($item->isChild());
        $this->assertFalse($item2->isChild());
    }

    /**
     * @test
     */
    public function test_parent_slug_will_throw_for_non_child_items(): void
    {
        $this->expectException(LogicException::class);
        $route = $this->getRoute();
        $item = AdminMenuItem::fromRoute($route, [], );
        $item->parentSlug();
    }

    private function getRoute(): Route
    {
        return Route::create('/wp-admin/admin.php/foo', RoutingTestController::class, 'admin.my_page');
    }
}
