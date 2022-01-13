<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use LogicException;
use InvalidArgumentException;
use Tests\Core\RoutingTestCase;
use Snicco\Core\Routing\Route\Route;
use Tests\Core\fixtures\Middleware\FooMiddleware;
use Tests\Core\fixtures\Middleware\BarMiddleware;
use Snicco\Core\Routing\AdminDashboard\AdminMenuItem;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\RoutingConfigurator;
use Snicco\Core\Routing\RoutingConfigurator\AdminRoutingConfigurator;

final class AdminMenuTest extends RoutingTestCase
{
    
    private AdminRoutingConfigurator $configurator;
    
    protected function setUp() :void
    {
        parent::setUp();
        $this->configurator = $this->adminRouteConfigurator();
    }
    
    /** @test */
    public function an_admin_menu_will_not_be_configured_if_no_route_handler_is_defined()
    {
        $this->configurator->page('admin1', '/foo', Route::DELEGATE, [], null);
        $this->configurator->page(
            'admin2',
            '/bar',
            Route::DELEGATE,
            [
                AdminMenuItem::MENU_TITLE => 'My explicit menu title',
            ],
            null
        );
        
        $items = $this->configurator->items();
        
        $this->assertCount(0, $items);
    }
    
    /** @test */
    public function an_admin_menu_will_be_preconfigured_based_on_the_route()
    {
        $this->configurator->page(
            'admin.my_route',
            'admin.php/foo',
            RoutingTestController::class,
            [],
            null
        );
        $this->configurator->page(
            'admin.my.second_route',
            'admin.php/bar',
            RoutingTestController::class,
            [],
            null
        );
        
        $items = $this->configurator->items();
        
        $this->assertCount(2, $items);
        
        $first = $items[0];
        
        $this->assertSame('My Route', $first->menuTitle());
        $this->assertSame('My Route', $first->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $first->slug()->asString());
        $this->assertSame(null, $first->icon());
        $this->assertSame(null, $first->requiredCapability());
        $this->assertSame(null, $first->icon());
        
        $second = $items[1];
        
        $this->assertSame('Second Route', $second->menuTitle());
        $this->assertSame('Second Route', $second->pageTitle());
        $this->assertSame('/wp-admin/admin.php/bar', $second->slug()->asString());
        $this->assertSame(null, $second->icon());
        $this->assertSame(null, $second->requiredCapability());
        $this->assertSame(null, $second->icon());
    }
    
    /** @test */
    public function arguments_can_be_passed_explicitly_and_will_be_merged()
    {
        $this->configurator->page(
            'admin.my_route',
            'admin.php/foo',
            RoutingTestController::class,
            [
                AdminMenuItem::MENU_TITLE => 'My explicit menu title',
            ],
            null
        );
        
        $item = $this->configurator->items();
        
        $first = $item[0];
        $this->assertSame('My explicit menu title', $first->menuTitle());
        $this->assertSame('My explicit menu title', $first->pageTitle());
        $this->assertSame('/wp-admin/admin.php/foo', $first->slug()->asString());
        $this->assertSame(null, $first->icon());
        $this->assertSame(null, $first->requiredCapability());
        $this->assertSame(null, $first->icon());
    }
    
    /** @test */
    public function admin_menus_will_not_be_added_if_null_is_passed_explicitly_even_tho_a_controller_is_defined()
    {
        $this->configurator->page(
            'admin.redirect',
            'admin.php/foo',
            RoutingTestController::class,
            null,
            null
        );
        
        $item = $this->configurator->items();
        
        $this->assertCount(0, $item);
    }
    
    /** @test */
    public function sub_menu_items_can_be_added_by_passing_in_another_route()
    {
        $r1 = $this->configurator->page(
            'admin_parent',
            '/admin.php/parent',
            RoutingTestController::class,
            [],
        );
        
        $this->configurator->page(
            'admin_sub',
            '/admin.php/sub',
            RoutingTestController::class,
            [
                AdminMenuItem::MENU_TITLE => 'My explicit menu title',
            ],
            $r1,
        );
        
        $items = $this->configurator->items();
        
        $this->assertCount(2, $items);
        
        $this->assertSame(null, $items[0]->parentSlug());
        $this->assertSame('/wp-admin/admin.php/parent', $items[1]->parentSlug()->asString());
    }
    
    /** @test */
    public function sub_menu_items_can_be_added_in_a_parent_scope_closure()
    {
        $r1 = $this->configurator->page(
            'admin_parent',
            '/admin.php/parent',
            RoutingTestController::class,
            [],
        );
        
        $this->configurator->subPages($r1, function (AdminRoutingConfigurator $router) {
            $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
            $router->page('admin_sub_2', '/admin.php/sub2', RoutingTestController::class);
        });
        
        $items = $this->configurator->items();
        
        $this->assertCount(3, $items);
        
        $this->assertSame(null, $items[0]->parentSlug());
        $this->assertSame('/wp-admin/admin.php/parent', $items[1]->parentSlug()->asString());
        $this->assertSame('/wp-admin/admin.php/parent', $items[2]->parentSlug()->asString());
    }
    
    /** @test */
    public function test_exception_for_calling_recursive_subpages()
    {
        $r1 = $this->configurator->page(
            'admin_parent',
            '/admin.php/parent',
            RoutingTestController::class,
            [],
        );
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Nested calls');
        
        $this->configurator->subPages($r1, function (AdminRoutingConfigurator $router) {
            $r2 = $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
            
            $this->configurator->subPages($r2, function () { });
        });
    }
    
    /** @test */
    public function test_exception_for_passing_a_route_inside_childPages()
    {
        $r1 = $this->configurator->page(
            'admin_parent',
            '/admin.php/parent',
            RoutingTestController::class,
            [],
        );
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'You can not pass route/parent_slug [admin_sub_1] as the last argument during a call to subPages().'
        );
        
        $this->configurator->subPages($r1, function (AdminRoutingConfigurator $router) {
            $r2 = $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
            $router->page('admin_sub_2', '/admin.php/sub2', RoutingTestController::class, [], $r2);
        });
    }
    
    /** @test */
    public function test_routing_to_subpages_works()
    {
        $r1 = $this->configurator->page(
            'admin_parent',
            '/admin.php/parent',
            RoutingTestController::class,
            [],
        );
        
        $this->configurator->subPages($r1, function (AdminRoutingConfigurator $router) {
            $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
        });
        
        $this->runKernel($this->adminRequest('GET', 'sub1'))
             ->assertOk()
             ->assertSee(RoutingTestController::static);
    }
    
    /** @test */
    public function calling_sub_pages_will_apply_all_middleware_of_the_parent_group()
    {
        $r1 = $this->configurator->page(
            'admin_parent',
            '/admin.php/parent',
            RoutingTestController::class,
            [],
        )->middleware(FooMiddleware::class);
        
        $this->configurator->subPages($r1, function (AdminRoutingConfigurator $router) {
            $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class);
        });
        
        $this->runKernel($this->adminRequest('GET', 'sub1'))
             ->assertOk()
             ->assertSee(RoutingTestController::static.':foo_middleware');
    }
    
    /** @test */
    public function it_works_with_nested_grouping_and_supPages()
    {
        $this->configurator->group(function (AdminRoutingConfigurator $router) {
            $r1 = $router->page(
                'admin_parent',
                '/admin.php/parent',
                RoutingTestController::class,
                [],
            )->middleware(FooMiddleware::class);
            
            $router->subPages($r1, function (AdminRoutingConfigurator $router) {
                $router->page('admin_sub_1', '/admin.php/sub1', RoutingTestController::class)
                       ->middleware(BarMiddleware::class);
            });
        }, [RoutingConfigurator::MIDDLEWARE_KEY => [RoutingConfigurator::ADMIN_MIDDLEWARE]]);
        
        $this->runKernel($this->adminRequest('GET', 'sub1'))
             ->assertOk()
             ->assertSee(RoutingTestController::static.':bar_middleware:foo_middleware');
    }
    
    /** @test */
    public function parent_pages_can_be_added_by_slug_only()
    {
        $r1 = $this->configurator->page(
            'options.sub',
            '/options.php/sub',
            RoutingTestController::class,
            [
                AdminMenuItem::MENU_TITLE => 'menu title',
                AdminMenuItem::PAGE_TITLE => 'page title',
            ],
            'options.php'
        );
        
        $items = $this->configurator->items();
        
        $this->assertCount(1, $items);
        
        $item = $items[0];
        
        $this->assertSame('menu title', $item->menuTitle());
        $this->assertSame('page title', $item->pageTitle());
        $this->assertSame('/wp-admin/options.php', $item->parentSlug()->asString());
        $this->assertSame('/wp-admin/options.php/sub', $item->slug()->asString());
    }
    
    /** @test */
    public function test_exception_if_parent_pages_with_slug_only_are_prefix_with_the_admin_area_prefix()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "You should not add the prefix [/wp-admin] to the parent slug of pages.\nAffected route [options.sub]"
        );
        
        $r1 = $this->configurator->page(
            'options.sub',
            '/options.php/sub',
            RoutingTestController::class,
            [
                AdminMenuItem::MENU_TITLE => 'menu title',
                AdminMenuItem::PAGE_TITLE => 'page title',
            ],
            '/wp-admin/options.php'
        );
    }
    
    /** @test */
    public function test_exception_if_parent_is_used_that_has_no_menu_item()
    {
        $r1 = $this->configurator->page(
            'options.redirect',
            '/options.php',
        );
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Can not use route [options.redirect] as a parent for [options.sub] because it has no menu item."
        );
        
        $r2 = $this->configurator->page(
            'options.sub',
            '/options.php/sub',
            RoutingTestController::class,
            [],
            $r1
        );
    }
    
    /** @test */
    public function test_exception_for_parents_by_slug_if_path_is_incompatible()
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Route pattern [/wp-admin/admin.php/sub] is incompatible with parent slug [/wp-admin/options.php].'
        );
        
        $r1 = $this->configurator->page(
            'options.sub',
            '/admin.php/sub',
            RoutingTestController::class,
            [
                AdminMenuItem::MENU_TITLE => 'menu title',
                AdminMenuItem::PAGE_TITLE => 'page title',
            ],
            'options.php'
        );
    }
    
    /** @test */
    public function test_exception_for_incompatible_path_with_parent_as_route()
    {
        $this->expectExceptionMessage(
            'Route pattern [/wp-admin/options.php/sub] is incompatible with parent slug [/wp-admin/admin.php/parent].'
        );
        
        $r1 = $this->configurator->page('page1', '/admin.php/parent', RoutingTestController::class);
        
        $r2 = $this->configurator->page(
            'page2',
            '/options.php/sub',
            RoutingTestController::class,
            [],
            $r1
        );
    }
    
    /** @test */
    public function test_exception_if_a_subpage_is_used_as_a_parent_page()
    {
        $r1 = $this->configurator->page(
            'admin.parent',
            '/admin.php/parent',
            RoutingTestController::class,
        );
        $r2 = $this->configurator->page(
            'admin.sub1',
            '/admin.php/sub1',
            RoutingTestController::class,
            [],
            $r1
        );
        
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'Cannot use route [admin.sub1] as a parent for route [admin.sub2] because [admin.sub1] is already a child route with parent slug [/wp-admin/admin.php/parent].'
        );
        
        $r3 = $this->configurator->page(
            'admin.sub2',
            '/admin.php/sub2',
            RoutingTestController::class,
            [],
            $r2
        );
    }
    
    /** @test */
    public function test_exception_if_parent_page_is_not_string_or_route()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('$parent has to be a string or an instance of Route.');
        
        $r2 = $this->configurator->page(
            'admin.sub1',
            '/admin.php/sub1',
            RoutingTestController::class,
            [],
            1
        );
    }
    
    /** @test */
    public function test_is_iterator()
    {
        $this->configurator->page('page1', 'admin.php/foo', RoutingTestController::class);
        $this->configurator->page('page2', 'admin.php/bar', RoutingTestController::class);
        $this->configurator->page('page3', 'admin.php/baz', RoutingTestController::class);
        
        $count = 0;
        foreach ($this->configurator as $key => $item) {
            $this->assertIsInt($key);
            $this->assertInstanceOf(AdminMenuItem::class, $item);
            $count++;
        }
    }
    
}


