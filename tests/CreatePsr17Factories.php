<?php


    declare(strict_types = 1);


    namespace Tests;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use Tests\stubs\TestViewService;
    use WPEmerge\Http\HttpResponseFactory;

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

        public function responseFactory () : HttpResponseFactory
        {

            return new HttpResponseFactory(
                new TestViewService(),
                $this->psrResponseFactory(),
                $this->psrStreamFactory()
            );

        }

    }