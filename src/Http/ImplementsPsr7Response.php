<?php


    declare(strict_types = 1);


    namespace WPEmerge\Http;

    use Psr\Http\Message\ResponseInterface;
    use Psr\Http\Message\StreamInterface;

    trait ImplementsPsr7Response
    {

        /**
         * @var ResponseInterface
         */
        private $psr7_response;

        public function getProtocolVersion()
        {
            return $this->psr7_response->getProtocolVersion();
        }

        public function withProtocolVersion($version)
        {
            $this->psr7_response =  $this->psr7_response->withProtocolVersion($version);
            return $this;
        }

        public function getHeaders()
        {
            return $this->psr7_response->getHeaders();
        }

        public function hasHeader($name)
        {
            return $this->psr7_response->hasHeader($name);
        }

        public function getHeader($name)
        {
            return $this->psr7_response->getHeader($name);

        }

        public function getHeaderLine($name)
        {
            return $this->psr7_response->getHeaderLine($name);
        }

        public function withHeader($name, $value)
        {
            $this->psr7_response = $this->psr7_response->withHeader($name, $value);
            return $this;
        }

        public function withAddedHeader($name, $value)
        {
            $this->psr7_response = $this->psr7_response-> withAddedHeader($name, $value);
            return $this;
        }

        public function withoutHeader($name)
        {
            $this->psr7_response = $this->psr7_response->withoutHeader($name);
            return $this;

        }

        public function getBody()
        {
            return $this->psr7_response->getBody();
        }

        public function withBody(StreamInterface $body)
        {
            $this->psr7_response = $this->psr7_response->withBody($body);
            return $this;
        }

        public function getStatusCode()
        {
            return $this->psr7_response->getStatusCode();

        }

        public function withStatus($code, $reasonPhrase = '')
        {
            $this->psr7_response = $this->psr7_response->withStatus($code, $reasonPhrase);
            return $this;
        }

        public function getReasonPhrase()
        {
            return $this->psr7_response->getReasonPhrase();
        }

    }