<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Contracts;

    use WP_User;
    use WPEmerge\Auth\Exceptions\FailedAuthenticationException;
    use WPEmerge\Http\Psr7\Request;

    interface Authenticator
    {

        /**
         * @param  Request  $request
         *
         * @return WP_User
         * @throws FailedAuthenticationException
         */
        public function authenticate(Request $request) : WP_User;

        public function view() :string;


    }