<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Concerns;

    use PHPUnit\Framework\Assert as PHPUnit;
    use WP_User;

    trait InteractsWithWordpressUsers
    {

        protected function createAdmin(array $args = []) : WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'administrator',
            ], $args));

        }

        protected function createEditor(array $args = []) : WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'editor',
            ], $args));

        }

        protected function createAuthor(array $args = []) : WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'author',
            ], $args));

        }

        protected function createSubscriber(array $args = []) : WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'subscriber',
            ], $args));

        }

        protected function assertUserDeleted($user)
        {

            $user_id = $this->normalizeUser($user);

            $user = get_user_by('id', $user_id);

            PHPUnit::assertNotInstanceOf(WP_User::class, $user, "The user [$user_id] still exists.");


        }

        protected function assertUserNotDeleted($user)
        {

            $user_id = $this->normalizeUser($user);

            $user = get_user_by('id', $user_id);

            PHPUnit::assertInstanceOf(WP_User::class, $user, "The user [$user_id] does not exists.");


        }

        private function normalizeUser($user) : int
        {

            return $user instanceof WP_User ? $user->ID : $user;

        }

    }