<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\Routing\Admin;

use PHPUnit\Framework\TestCase;
use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;

/**
 * @internal
 *
 * @psalm-internal Snicco
 */
final class WPAdminAreaTest extends TestCase
{
    /**
     * @test
     *
     * @psalm-suppress MixedOperand
     * @psalm-suppress MixedAssignment
     */
    public function the_login_url_can_come_from_a_callback(): void
    {
        $admin_area = new WPAdminArea('/wp-admin', function () {
            static $count = 0;
            ++$count;

            return 'login/' . (string) $count;
        });

        $this->assertSame('/login/1', $admin_area->loginPath());
        $this->assertSame('/login/2', $admin_area->loginPath());
        $this->assertSame('/login/3', $admin_area->loginPath());

        $admin_area = new WPAdminArea('/wp-admin', function () {
            static $count = 0;
            ++$count;

            return '/login/' . (string) $count . '/';
        });

        $this->assertSame('/login/1/', $admin_area->loginPath());
        $this->assertSame('/login/2/', $admin_area->loginPath());
        $this->assertSame('/login/3/', $admin_area->loginPath());
    }
}
