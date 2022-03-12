<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\Routing\Cache\FileRouteCache;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Conditions\MaybeRouteCondition;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use function is_file;

/**
 * @internal
 */
final class RouteCachingTest extends HttpRunnerTestCase
{
    private string $route_cache_file;

    protected function setUp(): void
    {
        parent::setUp();

        $this->route_cache_file = __DIR__ . '/__generated_snicco_wp_routes.php';

        $this->assertFalse(is_file($this->route_cache_file));
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (is_file($this->route_cache_file)) {
            unlink($this->route_cache_file);
        }
    }

    /**
     * @test
     */
    public function a_route_can_be_run_when_no_cache_files_exist_yet(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
        }, new FileRouteCache($this->route_cache_file));

        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('foo'));
    }

    /**
     * @test
     */
    public function a_cache_file_is_created_after_the_routes_are_loaded_for_the_first_time(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
        }, new FileRouteCache($this->route_cache_file));

        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('foo'));

        $this->assertTrue(is_file($this->route_cache_file));
    }

    /**
     * @test
     */
    public function routes_can_be_read_from_the_cache_and_match_without_needing_to_define_them(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
            $configurator->get('bar', '/bar', RoutingTestController::class);
            $configurator->get('baz', '/baz', RoutingTestController::class);
            $configurator->get('biz', '/biz', RoutingTestController::class);
            $configurator->get('boom', '/boom', RoutingTestController::class)->condition(
                MaybeRouteCondition::class,
                true
            );
            $configurator->get('bang', '/bang', RoutingTestController::class)->condition(
                MaybeRouteCondition::class,
                false
            );
        }, new FileRouteCache($this->route_cache_file));

        // Creates the cache file
        $routing->routes();

        // Simulate a new request with empty routes.
        $this->webRouting(function (): void {
        }, new FileRouteCache($this->route_cache_file));

        $request = $this->frontendRequest('foo');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('bar');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('biz');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('baz');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('boom');
        $this->assertResponseBody(RoutingTestController::static, $request);

        $request = $this->frontendRequest('bang');
        $this->assertResponseBody('', $request);
    }

    /**
     * @test
     */
    public function reverse_routing_works_with_cached_router(): void
    {
        $routing = $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
            $configurator->get('bar', '/bar', RoutingTestController::class);
        }, new FileRouteCache($this->route_cache_file));

        // Trigger reload
        $routing->routes();
        $routing = $this->webRouting(function (): void {
        }, new FileRouteCache($this->route_cache_file));

        $this->assertSame('/foo', $routing->urlGenerator()->toRoute('foo'));
        $this->assertSame('/bar', $routing->urlGenerator()->toRoute('bar'));
    }

    /**
     * @test
     */
    public function the_admin_menu_works_with_a_cached_router(): void
    {
        $routing = $this->adminRouting(function (AdminRoutingConfigurator $configurator): void {
            $configurator->page('foo', 'admin.php/foo', RoutingTestController::class);
            $configurator->page('bar', 'admin.php/bar', RoutingTestController::class);
            $configurator->page('baz', 'admin.php/baz', RoutingTestController::class);
        }, new FileRouteCache($this->route_cache_file));

        $admin_menu = $routing->adminMenu();
        $this->assertCount(3, $admin_menu->items(), 'Admin menu wrong pre cache');

        // Trigger reload
        $routing->routes();
        $routing = $this->webRouting(function (): void {
        }, new FileRouteCache($this->route_cache_file));

        $admin_menu = $routing->adminMenu();
        $this->assertCount(3, $admin_menu->items(), 'Admin menu wrong post cache');

        $items = $admin_menu->items();

        $this->assertTrue(isset($items[0]));
        $this->assertTrue(isset($items[1]));
        $this->assertTrue(isset($items[2]));

        $this->assertSame('/wp-admin/admin.php/foo', (string) $items[0]->slug());
        $this->assertSame('/wp-admin/admin.php/bar', (string) $items[1]->slug());
        $this->assertSame('/wp-admin/admin.php/baz', (string) $items[2]->slug());
    }
}

final class Controller
{
    public function handle(): string
    {
        return 'foo';
    }
}
