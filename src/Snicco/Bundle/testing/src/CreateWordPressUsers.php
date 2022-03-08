<?php

declare(strict_types=1);


namespace Snicco\Bundle\Testing;

use PHPUnit\Framework\Assert as PHPUnit;
use WP_UnitTest_Factory_For_User;
use WP_User;

use function get_user_by;

trait CreateWordPressUsers
{

    abstract protected function userFactory(): WP_UnitTest_Factory_For_User;

    protected function createAdmin(array $args = []): WP_User
    {
        return $this->createUserWithRole('administrator', $args);
    }

    protected function createEditor(array $args = []): WP_User
    {
        return $this->createUserWithRole('editor', $args);
    }

    protected function createSubscriber(array $args = []): WP_User
    {
        return $this->createUserWithRole('subscriber', $args);
    }

    protected function createAuthor(array $args = []): WP_User
    {
        return $this->createUserWithRole('author', $args);
    }

    protected function createContributor(array $args = []): WP_User
    {
        return $this->createUserWithRole('contributor', $args);
    }

    protected function createUserWithRole(string $user_role, array $args = []): WP_User
    {
        return $this->userFactory()->create_and_get(
            array_merge([
                'role' => $user_role,
            ], $args)
        );
    }

    /**
     * @param WP_User|int $user
     */
    protected function assertUserExists($user): void
    {
        $id = $user instanceof WP_User ? $user->ID : $user;
        PHPUnit::assertInstanceOf(WP_User::class, get_user_by('id', $id), "The user with id [$id] does not exist.");
    }

    /**
     * @param WP_User|int $user
     */
    protected function assertUserDoesntExists($user): void
    {
        $id = $user instanceof WP_User ? $user->ID : $user;
        PHPUnit::assertNotInstanceOf(WP_User::class, get_user_by('id', $id), "The user with id [$id] does exist.");
    }

}