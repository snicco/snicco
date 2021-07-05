<?php


    declare(strict_types = 1);


    namespace BetterWP\Middleware;

    use Psr\Http\Message\StreamInterface;

    class JsonPayload extends Payload
    {

        public function __construct($content_types = ['application/json'])
        {
            $this->content_types = $content_types;
        }

        protected function parse(StreamInterface $stream) :array
        {
            $json = trim( (string) $stream );

            if ($json === '') {

                return [];

            }

            return $this->decode($json);

        }

        private function decode(string $json)
        {

            $data = json_decode($json, true , 512,JSON_OBJECT_AS_ARRAY);
            $code = json_last_error();

            if ( $code !== JSON_ERROR_NONE ) {

                throw new \RuntimeException(
                    sprintf('JSON: %s', json_last_error_msg())
                );

            }

            return $data;

        }


    }