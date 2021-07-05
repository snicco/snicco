<?php


    declare(strict_types = 1);


    namespace WPMvc\Testing;

    use Psr\Http\Message\ResponseInterface;
    use WPMvc\Http\Cookies;
    use WPMvc\Http\ResponseEmitter;

    class TestResponseEmitter extends ResponseEmitter
    {

        /**
         * @var ResponseInterface
         */
        public $response;

        /**
         * @var Cookies
         */
        public $cookies;


        public function emit(ResponseInterface $response) : void
        {
            $this->response = new TestResponse($response);
        }

        public function emitCookies(Cookies $cookies)
        {
            $this->cookies = $cookies;
        }

        public function emitHeaders(ResponseInterface $response) : void
        {

            //

        }

    }