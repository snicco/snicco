<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Http\Cookies;
    use WPEmerge\Http\ResponseEmitter;

    class TestResponseEmitter extends ResponseEmitter
    {

        public function emit(ResponseInterface $response) : void
        {
            //
        }

        public function emitCookies(Cookies $cookies)
        {
            //
        }

        public function emitHeaders(ResponseInterface $response) : void
        {

            //

        }

    }