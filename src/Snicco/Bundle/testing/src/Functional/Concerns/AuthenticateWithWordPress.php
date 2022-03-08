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
     * @param WP_User|int $user
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
        PHPUnit::assertSame(0, $actual = wp_get_current_user()->ID, "The current user [$actual] is not a guest.");
    }

    /**
     * @param WP_User|int $user
     */
    final protected function assertIsAuthenticated($user): void
    {
        $expected_id = $user instanceof WP_User ? $user->ID : $user;

        $actual = wp_get_current_user()->ID;

        PHPUnit::assertNotSame(0, $actual, 'The current user is a guest.');
        PHPUnit::assertSame(
            $expected_id,
            $actual,
            "The current user [$actual] is not the expected one [$expected_id].",
        );
    }

}