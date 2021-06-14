<?php


    declare(strict_types = 1);


    namespace WPEmerge\EnhancedAuth;

    use WP_User;
    use WPEmerge\EnhancedAuth\Exceptions\FailedAuthenticationException;
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


    }