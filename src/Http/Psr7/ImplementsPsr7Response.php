<?php

declare(strict_types=1);

namespace Snicco\Http\Psr7;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

trait ImplementsPsr7Response
{
    
    protected ResponseInterface $psr7_response;
    
    public function withProtocolVersion($version)
    {
        return $this->new($this->psr7_response->withProtocolVersion($version));
    }
    
    public function withHeader($name, $value)
    {
        return $this->new($this->psr7_response->withHeader($name, $value));
    }
    
    public function withAddedHeader($name, $value)
    {
        return $this->new($this->psr7_response->withAddedHeader($name, $value));
    }
    
    public function withoutHeader($name)
    {
        return $this->new($this->psr7_response->withoutHeader($name));
    }
    
    public function withBody(StreamInterface $body)
    {
        return $this->new($this->psr7_response->withBody($body));
    }
    
    public function withStatus($code, $reasonPhrase = '')
    {
        return $this->new($this->psr7_response->withStatus($code, $reasonPhrase));
    }
    
    public function getProtocolVersion()
    {
        return $this->psr7_response->getProtocolVersion();
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
    
    public function getBody()
    {
        return $this->psr7_response->getBody();
    }
    
    public function getStatusCode()
    {
        return $this->psr7_response->getStatusCode();
    }
    
    public function getReasonPhrase()
    {
        return $this->psr7_response->getReasonPhrase();
    }
    
}