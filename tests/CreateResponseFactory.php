<?php


    declare(strict_types = 1);


    namespace Tests;

    use Http\Message\ResponseFactory;
    use Nyholm\Psr7\Factory\HttplugFactory;

    trait CreateResponseFactory
    {

        public function createFactory () : ResponseFactory
        {

            return new HttplugFactory();

        }

    }