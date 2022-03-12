<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Admin;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\Admin\CachedAdminMenu;

use function serialize;

/**
 * @internal
 */
final class CachedAdminMenuTest extends TestCase
{
    /**
     * @test
     */
    public function test_create_from_serialized_data(): void
    {
        $item1 = new AdminMenuItem('foo_title', 'foo_slug', 'foo_slug');
        $item2 = new AdminMenuItem('bar_title', 'bar_slug', 'bar_slug');

        $menu = new CachedAdminMenu([serialize($item1), serialize($item2)]);

        $this->assertCount(2, $menu->items());

        foreach ($menu->items() as $item) {
            $this->assertInstanceOf(AdminMenuItem::class, $item);
        }

        $items = $menu->items();

        $this->assertTrue(isset($items[0]));
        $this->assertTrue(isset($items[1]));

        $this->assertEquals(new AdminMenuItem('foo_title', 'foo_slug', 'foo_slug'), $items[0]);
        $this->assertEquals(new AdminMenuItem('bar_title', 'bar_slug', 'bar_slug'), $items[1]);
    }

    /**
     * @test
     */
    public function test_exception_for_corrupted_cache(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('unserialize');

        $item1 = new AdminMenuItem('foo_title', 'foo_slug', 'foo_slug');
        $item2 = new AdminMenuItem('bar_title', 'bar_slug', 'bar_slug');

        $menu = new CachedAdminMenu([serialize($item1), serialize($item2), serialize('foo')]);

        $menu->items();
    }
}
