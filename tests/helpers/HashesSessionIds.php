<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    trait HashesSessionIds
    {

        private function hash($id) {

            return hash( 'sha256', $id );

        }

        private function hashedSessionId() {

            return $this->hash($this->getSessionId());

        }

        private function getSessionId() : string
        {

            return  str_repeat('a', 64) ;

        }

        private function anotherSessionId() : string
        {

            return str_repeat('b', 64);
        }

    }