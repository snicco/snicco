<?php

declare(strict_types=1);

namespace Snicco\Bundle\Testing\Functional\Concerns;

use PHPUnit\Framework\Assert as PHPUnit;
use Webmozart\Assert\Assert;
use WP_UnitTest_Factory_For_User;
use WP_User;

use function get_user_by;

trait CreateWordPressUsers
{
    abstract protected function userFactory(): WP_UnitTest_Factory_For_User;

    final protected function createAdmin(array $args = []): WP_User
    {
        return $this->createUserWithRole('administrator', $args);
    }

    final protected function createEditor(array $args = []): WP_User
    {
        return $this->createUserWithRole('editor', $args);
    }

    final protected function createSubscriber(array $args = []): WP_User
    {
        return $this->createUserWithRole('subscriber', $args);
    }

    final protected function createAuthor(array $args = []): WP_User
    {
        return $this->createUserWithRole('author', $args);
    }

    final protected function createContributor(array $args = []): WP_User
    {
        return $this->createUserWithRole('contributor', $args);
    }

    final protected function createUserWithRole(string $user_role, array $args = []): WP_User
    {
        $user = $this->userFactory()
            ->create_and_get(array_merge([
                'role' => $user_role,
            ], $args));
        Assert::isInstanceOf($user, WP_User::class);

        return $user;
    }

    /**
     * @param int|WP_User $user
     */
    final protected function assertUserExists($user): void
    {
        $id = $user instanceof WP_User ? $user->ID : $user;
        PHPUnit::assertInstanceOf(
            WP_User::class,
            get_user_by('id', $id),
            sprintf('The user with id [%s] does not exist.', $id)
        );
    }

    /**
     * @param int|WP_User $user
     */
    final protected function assertUserDoesntExists($user): void
    {
        $id = $user instanceof WP_User ? $user->ID : $user;
        PHPUnit::assertNotInstanceOf(
            WP_User::class,
            get_user_by('id', $id),
            sprintf('The user with id [%s] does exist.', $id)
        );
    }
}
