<?php

declare(strict_types=1);

namespace Snicco\Bundle\HttpRouting\Tests\wordpress;

use Codeception\TestCase\WPTestCase;
use LogicException;
use Snicco\Bridge\Pimple\PimpleContainerAdapter;
use Snicco\Bundle\HttpRouting\WPAdminMenu;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\Admin\CachedAdminMenu;
use Snicco\Component\Kernel\Kernel;
use Snicco\Component\Kernel\ValueObject\Directories;
use Snicco\Component\Kernel\ValueObject\Environment;
use WP_UnitTest_Factory_For_User;
use WP_User;

use function dirname;
use function do_action;
use function remove_all_filters;
use function serialize;
use function wp_set_current_user;

/**
 * @psalm-suppress UnnecessaryVarAnnotation
 *
 * @internal
 */
final class WPAdminMenuTest extends WPTestCase
{
    private Kernel $kernel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kernel = new Kernel(
            new PimpleContainerAdapter(),
            Environment::testing(),
            Directories::fromDefaults(dirname(__DIR__) . '/fixtures')
        );
        $this->kernel->boot();

        /**
         * @var WP_UnitTest_Factory_For_User $user_factory
         * @psalm-suppress MixedPropertyFetch
         */
        $user_factory = $this->factory()
            ->user;

        /** @var WP_User $user */
        $user = $user_factory->create_and_get([
            'role' => 'administrator',
        ]);
        wp_set_current_user($user->ID);
    }

    /**
     * @test
     */
    public function the_wp_admin_menu_can_be_resolved(): void
    {
        global $menu;
        global $submenu;
        $menu = [];
        $submenu = [];

        remove_all_filters('admin_menu');

        /** @var WPAdminMenu $wp_admin_menu */
        $wp_admin_menu = $this->kernel->container()
            ->make(WPAdminMenu::class);

        $wp_admin_menu->setUp();

        do_action('admin_menu');

        $this->assertCount(4, $menu);

        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame([
            'server_error' => [
                [
                    0 => 'Server Error',
                    1 => 'manage_options',
                    2 => 'server_error',
                    3 => 'Server Error',
                ],

                [
                    0 => 'Do Nothing',
                    1 => 'manage_options',
                    2 => 'do_nothing',
                    3 => 'Do Nothing',
                ],
            ],
        ], $submenu);
    }

    /**
     * @test
     */
    public function the_admin_menu_is_built_correctly(): void
    {
        global $menu;
        global $submenu;
        $menu = [];
        $submenu = [];

        remove_all_filters('admin_menu');

        /** @var WPAdminMenu $wp_admin_menu */
        $wp_admin_menu = $this->kernel->container()
            ->make(WPAdminMenu::class);

        $wp_admin_menu->setUp();

        do_action('admin_menu');

        $this->assertCount(4, $menu);
        /** @psalm-suppress TypeDoesNotContainType */
        $this->assertSame([
            'server_error' => [
                [
                    0 => 'Server Error',
                    1 => 'manage_options',
                    2 => 'server_error',
                    3 => 'Server Error',
                ],

                [
                    0 => 'Do Nothing',
                    1 => 'manage_options',
                    2 => 'do_nothing',
                    3 => 'Do Nothing',
                ],
            ],
        ], $submenu);

        $this->assertTrue(isset($menu[-10]));
        $this->assertTrue(isset($menu[0]));
        $this->assertTrue(isset($menu[1]));
        $this->assertTrue(isset($menu[2]));

        $this->assertTrue(isset($submenu['server_error']));

        /** @var list<string> $foo */
        $foo = $menu[0];

        $this->assertSame('FOO_TITLE', $foo[0] ?? '');
        $this->assertSame('read', $foo[1] ?? '');
        $this->assertSame('foo', $foo[2] ?? '');
    }

    /**
     * @test
     */
    public function test_throws_exception_if_a_slug_does_not_contain_a_php_file_in_slug(): void
    {
        $item = new AdminMenuItem('foo_title', 'par_title', '/wp-admin/foo_slug');
        $admin_menu = new CachedAdminMenu([serialize($item)]);

        $wp_admin_menu = new WPAdminMenu($admin_menu);

        $wp_admin_menu->setUp();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            "Admin menu item with slug [/wp-admin/foo_slug] is miss configured as it does not contain a path-segment with '.php'.\nPlease check your admin.php route file."
        );

        do_action('admin_menu');
    }
}
