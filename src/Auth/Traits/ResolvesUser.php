<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Traits;

    use WP_User;

    trait ResolvesUser
    {

        public function getUserByLogin(string $login ) {

            $is_email = filter_var($login, FILTER_VALIDATE_EMAIL);

            return $is_email
                ? get_user_by('email', trim(wp_unslash($login)))
                : get_user_by('login', trim(wp_unslash($login)));

        }

        public function getUserById($id) : ?WP_User {

            $user = get_user_by('id', $id);

            return $user instanceof WP_User ? $user :null;

        }


    }