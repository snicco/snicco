<?php


    declare(strict_types = 1);


    namespace WPEmerge\Session;

    use SessionHandlerInterface;
    use WPEmerge\Http\Psr7\Request;

    interface SessionDriver extends SessionHandlerInterface
    {


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
         * @param  string  $hashed_id
         *
         * @return bool
         */
        public function isValid(string $hashed_id ) :bool;

        /**
         * @param  int  $user_id
         *
         * @return array<string> An array of serialized session data
         */
        public function getAllByUser(int $user_id) :array;

        /**
         *
         * Destroy all session for the user with the provided id
         * except the the one for the provided token.
         *
         * @param  string  $hashed_token
         * @param  int  $user_id
         *
         */
        public function destroyOthersForUser(string $hashed_token, int $user_id);


        /**
         *
         * Destroy all session for the user with the provided id
         *
         * @param  int  $user_id
         *
         */
        public function destroyAllForUser(int $user_id);

        /**
         * Destroy all sessions for every user.
         */
        public function destroyAll();


    }