<?php


    declare(strict_types = 1);


    namespace Tests\helpers;;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use Tests\stubs\TestViewFactory;
    use WPEmerge\Http\ResponseFactory;

    trait CreatePsr17Factories
    {

        public function psrResponseFactory() : ResponseFactoryInterface
        {

            return new Psr17Factory();

        }

        public function psrStreamFactory() : StreamFactoryInterface
        {

            return new Psr17Factory();

        }

        public function createResponseFactory () : ResponseFactory
        {

            return new ResponseFactory(
                new TestViewFactory(),
                $this->psrResponseFactory(),
                $this->psrStreamFactory()
            );

        }

        public static function __callStatic($name, $arguments)
        {
            return static::{$name}($arguments);
        }

    }