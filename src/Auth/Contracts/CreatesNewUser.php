<?php


    declare(strict_types = 1);


    namespace BetterWP\Auth\Contracts;


    use BetterWP\Http\Psr7\Request;

    interface CreatesNewUser
    {

        /**
         *
         * Validate and create a new WP_User for the given request.
         *
         * @param  Request  $request
         *
         * @return int The new users id.
         */
        public function create(Request $request) :int;


    }