<?php


    declare(strict_types = 1);


    namespace Tests\integration\Blade\traits;

    use function wp_delete_user;
    use function wp_logout;
    use function wp_set_current_user;

    trait InteractsWithWordpress
    {

        public function newAdmin(array $args = [])
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'administrator'
            ], $args));

        }

        public function newEditor(array $args = [])
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'editor'
            ], $args));

        }

        public function newAuthor(array $args = [])
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'author'
            ], $args));

        }

        public function login ( $user ) {

            wp_logout();

            if ( $user instanceof  \WP_User ) {

                wp_set_current_user($user->ID);

                return;

            }

            wp_set_current_user($user);

        }

        public function logout($user = null ) {

            if ( $user ) {

                wp_delete_user($user);

            }

            wp_logout();

        }

    }