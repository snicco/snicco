<?php


    declare(strict_types = 1);


    namespace WPEmerge\Testing;

    use WPEmerge\Http\Psr7\Response;

    class TestResponse
    {

        /**
         * @var Response
         */
        private $response;

        public function __construct(Response $response)
        {
            $this->response = $response;
        }

    }