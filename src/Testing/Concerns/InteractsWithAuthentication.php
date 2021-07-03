<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Concerns;

    use PHPUnit\Framework\Assert as PHPUnit;
    use WP_User;

    use WPEmerge\Session\Session;

    use function wp_delete_user;
    use function wp_get_current_user;
    use function wp_logout;
    use function wp_set_current_user;

    /**
     * @property Session $session
     */
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
            $this->session->confirmAuthUntil($this->config->get('auth.confirmation.duration', 10 ));
            $this->session->setLastActivity(time());
            $this->withSessionCookie();

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
        protected function assertNotAuthenticated($user) {

            if ($user instanceof WP_User) {

                $user = $user->ID;

            }

            PHPUnit::assertFalse($this->isAuthenticated($user), 'The user is authenticated.');

        }

        /**
         * @param  int|WP_User  $user
         */
        protected function logout($user = 0)
        {

            if ( $user === 0 ) {
                $user = wp_get_current_user();
            }

            $user = $user instanceof WP_User ? $user->ID : $user;

            if ( $user === 0 ) {
                return;
            }

            wp_logout();

        }

        private function isAuthenticated(int $user_id) : bool
        {

            return wp_get_current_user()->ID === $user_id;

        }


    }