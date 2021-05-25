<?php


    declare(strict_types = 1);


    namespace Tests\stubs;

    use Psr\Http\Message\ResponseInterface;
    use WPEmerge\Http\ResponseEmitter;

    class TestResponseEmitter extends ResponseEmitter
    {

        public $headers = [];
        /**
         * @var string
         */
        public $status_line;

        /**
         * Send the response the client
         *
         * @param  ResponseInterface  $response
         *
         * @return void
         */
        public function emit(ResponseInterface $response) : void
        {

            $isEmpty = $this->isResponseEmpty($response);

            $this->emitStatusLine($response);
            $this->emitHeaders($response);

            if ( ! $isEmpty ) {

                $this->emitBody($response);

            }
        }

        /**
         * Emit Response Headers
         *
         * @param  ResponseInterface  $response
         */
        protected function emitHeaders(ResponseInterface $response) : void
        {

            foreach ($response->getHeaders() as $name => $values) {

                foreach ($values as $value) {

                    $header = sprintf('%s: %s', $name, $value);
                    $this->headers[] = $header;

                }
            }

        }

        /**
         * Emit Status Line
         *
         * @param  ResponseInterface  $response
         */
        protected function emitStatusLine(ResponseInterface $response) : void
        {

            $statusLine = sprintf(
                'HTTP/%s %s %s',
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase()
            );

            $this->status_line = $statusLine;

        }

        protected function emitBody(ResponseInterface $response) : void
        {

            $body = $response->getBody();

            echo $body;


        }



    }