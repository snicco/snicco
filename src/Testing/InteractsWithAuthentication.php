<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use PHPUnit\Framework\Assert as PHPUnit;
    use WP_User;

    trait InteractsWithAuthentication
    {

        /**
         * @param  array|WP_User|int  $user
         */
        protected function actingAs($user)
        {

            wp_logout();

            if (is_int($user)) {

                wp_set_current_user($user);

                return;

            }

            if (is_array($user)) {

                $user = $this->factory()->user->create_and_get(array_merge([
                    'role' => 'administrator',
                ], $user));

            }

            wp_set_current_user($user->ID);


        }

        protected function assertGuest()
        {

            PHPUnit::assertSame(0, wp_get_current_user()->ID, 'The user is not a guest.');

        }

        /**
         * @param  int|WP_User  $user
         */
        protected function assertAuthenticated($user)
        {

            if ($user instanceof WP_User) {

                $user = $user->ID;

            }

            PHPUnit::assertTrue($this->isAuthenticated($user), 'The user is not authenticated.');

        }

        /**
         * @param  int|WP_User  $user
         */
        protected function logout($user)
        {

            $user = $user instanceof WP_User ? $user->ID : $user;

            wp_delete_user($user);

            wp_logout();

        }

        private function isAuthenticated(int $user_id) : bool
        {

            return wp_get_current_user()->ID === $user_id;

        }


    }