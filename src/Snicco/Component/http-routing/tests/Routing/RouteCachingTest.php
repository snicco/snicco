<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing;

use Snicco\Component\HttpRouting\Routing\Cache\CallbackRouteCache;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Conditions\MaybeRouteCondition;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\HttpRunnerTestCase;

use Snicco\Component\Kernel\Cache\PHPFileCache;

use function exec;
use function is_dir;
use function is_file;
use function sys_get_temp_dir;

/**
 * @internal
 */
final class RouteCachingTest extends HttpRunnerTestCase
{
    /**
     * @var non-empty-string
     */
    private string $cache_dir;

    /**
     * @var non-empty-string
     */
    private string $route_cache_file;

    private CallbackRouteCache $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cache_dir = sys_get_temp_dir() . '/http_routing_route_caching_test';
        if (is_dir($this->cache_dir)) {
            exec("rm -rf {$this->cache_dir}");
        }

        $this->route_cache_file = $this->cache_dir . '/test.routes.php';

        $this->cache = new CallbackRouteCache(function (callable $load_routes): array {
            $cache = new PHPFileCache($this->cache_dir);

            return $cache->getOr('test.routes', $load_routes);
        });
    }

    protected function tearDown(): void
    {
        if (is_dir($this->cache_dir)) {
            exec("rm -rf {$this->cache_dir}");
        }

        parent::tearDown();
    }

    /**
     * @test
     */
    public function a_route_can_be_run_when_no_cache_files_exist_yet(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
        }, $this->cache);

        $this->assertResponseBody(RoutingTestController::static, $this->frontendRequest('foo'));
    }

    /**
     * @test
     */
    public function a_cache_file_is_created_after_the_routes_are_loaded_for_the_first_time(): void
    {
        $this->webRouting(function (WebRoutingConfigurator $configurator): void {
            $configurator->get('foo', '/foo', RoutingTestController::class);
        }, $this->cache);

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
        }, $this->cache);

        // Creates the cache file
        $routing->routes();

        // Simulate a new request with empty routes.
        $this->webRouting(function (): void {
        }, $this->cache);

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
        }, $this->cache);

        // Trigger reload
        $routing->routes();
        $routing = $this->webRouting(function (): void {
        }, $this->cache);

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
        }, $this->cache);

        $admin_menu = $routing->adminMenu();
        $this->assertCount(3, $admin_menu->items(), 'Admin menu wrong pre cache');

        // Trigger reload
        $routing->routes();
        $routing = $this->webRouting(function (): void {
        }, $this->cache);

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
