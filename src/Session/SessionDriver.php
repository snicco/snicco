<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use SessionHandlerInterface;
    use WPEmerge\Http\Psr7\Request;

    interface SessionDriver extends SessionHandlerInterface
    {

        public function setRequest(Request $request);

        /**
         *
         * This function takes the session id from the session cookie.
         * The function should return true if the session driver has a valid
         * and NOT expired session for the provided session id.
         *
         * If no session is present for the given id OR the session is expired false should be returned.
         *
         * The session ID is user provided. The driver has to sanitize the input.
         *
         *
         * @param  string  $id
         *
         * @return bool
         */
        public function isValid(string $id ) :bool;


    }