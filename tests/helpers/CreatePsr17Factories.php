<?php


    declare(strict_types = 1);


    namespace Tests\helpers;

    use Nyholm\Psr7\Factory\Psr17Factory;
    use Psr\Http\Message\ResponseFactoryInterface;
    use Psr\Http\Message\ServerRequestFactoryInterface;
    use Psr\Http\Message\StreamFactoryInterface;
    use Psr\Http\Message\UploadedFileFactoryInterface;
    use Psr\Http\Message\UriFactoryInterface;
    use Snicco\Http\Redirector;
    use Snicco\Http\ResponseFactory;
    use Tests\stubs\TestViewFactory;

    /**
     * @internal
     */
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

        public function psrServerRequestFactory() : ServerRequestFactoryInterface
        {
            return new Psr17Factory();
        }

        public function psrUploadedFileFactory() : UploadedFileFactoryInterface
        {
            return new Psr17Factory();
        }

        public function psrUriFactory() : UriFactoryInterface
        {
            return new Psr17Factory();
        }

        public function createResponseFactory() : ResponseFactory
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