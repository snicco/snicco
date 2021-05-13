<?php


    declare(strict_types = 1);


    namespace WPEmerge;

    use Psr\Http\Message\MessageInterface;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use Psr\Http\Message\StreamInterface;
    use Psr\Http\Message\UriInterface;


    trait ImplementsPsr7Request
    {

        /**
         * @var RequestInterface|MessageInterface|ServerRequestInterface
         */
        private $prs_request;

        public function getProtocolVersion()
        {

            return $this->prs_request->getProtocolVersion();


        }

        public function withProtocolVersion($version)
        {
            return $this->prs_request->withProtocolVersion($version);
        }

        public function getHeaders()
        {
            return $this->prs_request->getHeaders();
        }

        public function hasHeader($name)
        {
            return $this->prs_request->hasHeader($name);
        }

        public function getHeader($name)
        {
            return $this->prs_request->getHeader($name);
        }

        public function getHeaderLine($name)
        {
            return $this->prs_request->getHeaderLine($name);
        }

        public function withHeader($name, $value)
        {
            return $this->prs_request->withHeader($name, $value);
        }

        public function withAddedHeader($name, $value)
        {
            return $this->prs_request->withAddedHeader($name, $value);
        }

        public function withoutHeader($name)
        {
            return $this->prs_request->withoutHeader($name);
        }

        public function getBody()
        {
            return $this->prs_request->getBody();
        }

        public function withBody(StreamInterface $body)
        {
            return $this->prs_request->withBody($body);
        }

        public function getRequestTarget()
        {
            return $this->prs_request->getRequestTarget();
        }

        public function withRequestTarget($requestTarget)
        {
            return $this->prs_request->withRequestTarget($requestTarget);
        }

        public function getMethod()
        {
            return $this->prs_request->getMethod();
        }

        public function withMethod($method)
        {
            return $this->prs_request->withMethod($method);
        }

        public function getUri()
        {
            return $this->prs_request->getUri();
        }

        public function withUri(UriInterface $uri, $preserveHost = false)
        {
            return $this->prs_request->withUri($uri, $preserveHost);
        }

        public function getServerParams()
        {
            return $this->prs_request->getServerParams();
        }

        public function getCookieParams()
        {
            return $this->prs_request->getCookieParams();
        }

        public function withCookieParams(array $cookies)
        {
            return $this->prs_request->withCookieParams($cookies);
        }

        public function getQueryParams()
        {

            return $this->prs_request->getQueryParams();

        }

        public function withQueryParams(array $query)
        {
            return $this->prs_request->withQueryParams($query);
        }

        public function getUploadedFiles()
        {
            return $this->prs_request->getUploadedFiles();
        }

        public function withUploadedFiles(array $uploadedFiles)
        {
            return $this->prs_request->withUploadedFiles($uploadedFiles);
        }

        public function getParsedBody()
        {
            return $this->prs_request->getParsedBody();
        }

        public function withParsedBody($data)
        {
            return $this->prs_request->withParsedBody($data);
        }

        public function getAttributes()
        {
            return $this->prs_request->getAttributes();
        }

        public function getAttribute($name, $default = null)
        {
            return $this->prs_request->getAttribute($name, $default);

        }

        public function withAttribute($name, $value)
        {
            return $this->prs_request->withAttribute($name, $value);
        }

        public function withoutAttribute($name)
        {
            return $this->prs_request->withoutAttribute($name);
        }


    }