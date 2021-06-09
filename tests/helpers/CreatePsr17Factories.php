<?php


    declare(strict_types = 1);


    namespace Tests\helpers;;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Psr\Http\Message\RequestFactoryInterface;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use Tests\stubs\TestViewFactory;
    use WPEmerge\Http\Redirector;
    use WPEmerge\Http\ResponseFactory;
    use WPEmerge\Routing\UrlGenerator;

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

        public function createRequestFactory() :ServerRequestFactoryInterface {

            return new Psr17Factory();

        }

        public function createResponseFactory () : ResponseFactory
        {

            return new ResponseFactory(
                new TestViewFactory(),
                $f = $this->psrResponseFactory(),
                $this->psrStreamFactory(),
                new Redirector($this->newUrlGenerator(TEST_APP_KEY), $f),
            );

        }

        public static function __callStatic($name, $arguments)
        {
            return static::{$name}($arguments);
        }

    }