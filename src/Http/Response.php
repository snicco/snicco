<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;

    class Response implements ResponseInterface
    {

        use ImplementsPsr7Response;

        /**
         * @var ResponseInterface
         */
        private $psr7_response;

        public function __construct(ResponseInterface $psr7_response)
        {

            $this->psr7_response = $psr7_response;
        }

        public function html() : Response
        {

            return $this->withHeader('Content-Type', 'text/html');

        }

        public function json() : Response
        {

            return $this->withHeader('Content-Type', 'application/json');

        }


    }