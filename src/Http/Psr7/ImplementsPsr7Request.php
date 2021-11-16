<?php

declare(strict_types=1);

namespace Snicco\Http\Psr7;

use Psr\Http\Message\UriInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ServerRequestInterface;

trait ImplementsPsr7Request
{
    
    private ServerRequestInterface $psr_request;
    
    public function withProtocolVersion($version)
    {
        return $this->new($this->psr_request->withProtocolVersion($version));
    }
    
    public function new(ServerRequestInterface $new_psr_request)
    {
        return new static($new_psr_request);
    }
    
    public function withHeader($name, $value)
    {
        return $this->new($this->psr_request->withHeader($name, $value));
    }
    
    public function withAddedHeader($name, $value)
    {
        return $this->new($this->psr_request->withAddedHeader($name, $value));
    }
    
    public function withoutHeader($name)
    {
        return $this->new($this->psr_request->withoutHeader($name));
    }
    
    public function withBody(StreamInterface $body)
    {
        return $this->new($this->psr_request->withBody($body));
    }
    
    public function withRequestTarget($requestTarget)
    {
        return $this->new($this->psr_request->withRequestTarget($requestTarget));
    }
    
    public function withMethod($method)
    {
        return $this->new($this->psr_request->withMethod($method));
    }
    
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        return $this->new($this->psr_request->withUri($uri, $preserveHost));
    }
    
    public function withQueryParams(array $query)
    {
        return $this->new($this->psr_request->withQueryParams($query));
    }
    
    public function withCookieParams(array $cookies)
    {
        return $this->new($this->psr_request->withCookieParams($cookies));
    }
    
    public function withAttribute($name, $value)
    {
        return $this->new($this->psr_request->withAttribute($name, $value));
    }
    
    public function withoutAttribute($name)
    {
        return $this->new($this->psr_request->withoutAttribute($name));
    }
    
    public function withParsedBody($data)
    {
        return $this->new($this->psr_request->withParsedBody($data));
    }
    
    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->new($this->psr_request->withUploadedFiles($uploadedFiles));
    }
    
    public function getProtocolVersion()
    {
        return $this->psr_request->getProtocolVersion();
    }
    
    public function getHeaders()
    {
        return $this->psr_request->getHeaders();
    }
    
    public function hasHeader($name)
    {
        return $this->psr_request->hasHeader($name);
    }
    
    public function getHeader($name)
    {
        return $this->psr_request->getHeader($name);
    }
    
    public function getHeaderLine($name)
    {
        return $this->psr_request->getHeaderLine($name);
    }
    
    public function getBody()
    {
        return $this->psr_request->getBody();
    }
    
    public function getRequestTarget()
    {
        return $this->psr_request->getRequestTarget();
    }
    
    public function getMethod()
    {
        return $this->psr_request->getMethod();
    }
    
    public function getUri()
    {
        return $this->psr_request->getUri();
    }
    
    public function getServerParams()
    {
        return $this->psr_request->getServerParams();
    }
    
    public function getCookieParams()
    {
        return $this->psr_request->getCookieParams();
    }
    
    public function getQueryParams()
    {
        return $this->psr_request->getQueryParams();
    }
    
    public function getUploadedFiles()
    {
        return $this->psr_request->getUploadedFiles();
    }
    
    public function getParsedBody()
    {
        return $this->psr_request->getParsedBody();
    }
    
    public function getAttributes()
    {
        return $this->psr_request->getAttributes();
    }
    
    public function getAttribute($name, $default = null)
    {
        return $this->psr_request->getAttribute($name, $default);
    }
    
}