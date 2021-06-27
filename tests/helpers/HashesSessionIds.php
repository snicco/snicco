<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    trait HashesSessionIds
    {

        protected function hash($id) {

            return hash( 'sha256', $id );

        }

        protected function hashedSessionId() {

            return $this->hash($this->getSessionId());

        }

        protected function getSessionId() : string
        {

            return  str_repeat('a', 64) ;

        }

        protected function anotherSessionId() : string
        {

            return str_repeat('b', 64);
        }

    }