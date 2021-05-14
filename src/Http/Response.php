<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;

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

        public function html(StreamInterface $html) : Response
        {

            return $this->withHeader('Content-Type', 'text/html')
                        ->withBody($html);

        }

        public function json(StreamInterface $json) : Response
        {

            return $this->withHeader('Content-Type', 'application/json')
                        ->withBody($json);

        }

        private function new(ResponseInterface $new_psr_response) : Response
        {

            return new static($new_psr_response);

        }

    }