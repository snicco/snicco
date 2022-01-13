<?php

declare(strict_types=1);

namespace Tests\Core\unit\Routing;

use Tests\Core\RoutingTestCase;
use Snicco\Core\Routing\Route\Route;
use Snicco\Core\Routing\AdminDashboard\AdminMenu;
use Snicco\Core\Routing\AdminDashboard\AdminMenuItem;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
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
        $this->configurator->admin('admin1', '/foo');
        $this->configurator->admin(
            'admin2',
            '/foo',
            Route::DELEGATE,
            new AdminMenuItem('title', 'title', 'foo')
        );
        
        $menu = new TestAdminMenu();
        $this->configurator->configureAdminMenu($menu);
        
        $this->assertCount(0, $menu->items);
    }
    
    /** @test */
    public function an_admin_menu_will_be_preconfigured_based_on_the_route()
    {
        $this->configurator->admin('admin.my_route', 'admin.php/foo', RoutingTestController::class);
        $this->configurator->admin(
            'admin.my.second_route',
            'admin.php/bar',
            RoutingTestController::class
        );
        $this->configurator->admin(
            'my third route',
            '/admin.php/baz',
            RoutingTestController::class
        );
        
        $menu = new TestAdminMenu();
        $this->configurator->configureAdminMenu($menu);
        
        $this->assertCount(3, $menu->items);
        
        $first = $menu->items[0];
        
        $this->assertSame('My Route', $first->menuTitle());
        $this->assertSame('My Route', $first->pageTitle());
        $this->assertSame('/foo', $first->slug()->asString());
        $this->assertSame(null, $first->icon());
        $this->assertSame(null, $first->requiredCapability());
        $this->assertSame(null, $first->icon());
        
        $second = $menu->items[1];
        
        $this->assertSame('Second Route', $second->menuTitle());
        $this->assertSame('Second Route', $second->pageTitle());
        $this->assertSame('/bar', $second->slug()->asString());
        $this->assertSame(null, $second->icon());
        $this->assertSame(null, $second->requiredCapability());
        $this->assertSame(null, $second->icon());
        
        $third = $menu->items[2];
        
        $this->assertSame('My Third Route', $third->menuTitle());
        $this->assertSame('My Third Route', $third->pageTitle());
        $this->assertSame('/baz', $third->slug()->asString());
        $this->assertSame(null, $third->icon());
        $this->assertSame(null, $third->requiredCapability());
        $this->assertSame(null, $third->icon());
    }
    
}

class TestAdminMenu implements AdminMenu
{
    
    /**
     * @var AdminMenuItem[]
     */
    public array $items = [];
    
    public function add(AdminMenuItem $menu_item) :void
    {
        $this->items[] = $menu_item;
    }
    
}

