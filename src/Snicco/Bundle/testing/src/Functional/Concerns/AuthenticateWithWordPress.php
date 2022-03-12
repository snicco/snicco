<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Functional\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use WP_User;

use function wp_get_current_user;
use function wp_set_current_user;

trait AuthenticateWithWordPress
{
    /**
     * @param int|WP_User $user
     */
    final protected function loginAs($user): void
    {
        $id = $user instanceof WP_User ? $user->ID : $user;
        wp_set_current_user($id);
    }

    final protected function logout(): void
    {
        wp_set_current_user(0);
    }

    final protected function assertIsGuest(): void
    {
        PHPUnit::assertSame(
            0,
            $actual = wp_get_current_user()
                ->ID,
            sprintf('The current user [%s] is not a guest.', $actual)
        );
    }

    /**
     * @param int|WP_User $user
     */
    final protected function assertIsAuthenticated($user): void
    {
        $expected_id = $user instanceof WP_User ? $user->ID : $user;

        $actual = wp_get_current_user()
            ->ID;

        PHPUnit::assertNotSame(0, $actual, 'The current user is a guest.');
        PHPUnit::assertSame(
            $expected_id,
            $actual,
            sprintf('The current user [%s] is not the expected one [%s].', $actual, $expected_id),
        );
    }
}
