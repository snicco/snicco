<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Responses;

    use WP_User;
    use WPEmerge\Http\Psr7\Response;

    class LoginResponse extends Response
    {

        /**
         * @var WP_User
         */
        private $user;

        private $remember = false;

        public function withUser(WP_User $user) : LoginResponse
        {

            $this->user = $user;

            return clone $this;

        }

        public function authenticatedUser() : WP_User
        {
            return $this->user;
        }

        public function rememberUser( bool $remember = null ) {

            if ( $remember === null ) {

                return $this->remember;

            }

            $this->remember = $remember;

        }

    }