<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing\Concerns;

    use WP_User;

    trait InteractsWithWordpressUsers
    {

        public function createAdmin(array $args = []) :WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'administrator'
            ], $args));

        }

        public function createEditor(array $args = []) :WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'editor'
            ], $args));

        }

        public function createAuthor(array $args = []) :WP_User
        {

            return $this->factory()->user->create_and_get(array_merge([
                'role' => 'author'
            ], $args));

        }

    }