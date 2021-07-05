<?php


    declare(strict_types = 1);


    namespace WPEmerge\Auth\Contracts;


    interface DeletesUsers
    {

        /**
         *
         * Return a non null value to reassign the users posts to the returned user id
         *
         * @param  int  $user_to_be_deleted
         *
         * @return int|null
         */
        public function reassign(int $user_to_be_deleted) :?int;

    }