<?php

declare(strict_types=1);

namespace Snicco\Core\Http\Psr7;

use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\ResponseInterface;

trait ImplementsPsr7Response
{
    
    /**
     * @var ResponseInterface
     */
    protected $psr7_response;
    
    public function withProtocolVersion($version) :self
    {
        return $this->new($this->psr7_response->withProtocolVersion($version));
    }
    
    /**
     * @return static
     */
    public function withHeader($name, $value) :self
    {
        return $this->new($this->psr7_response->withHeader($name, $value));
    }
    
    /**
     * @return static
     */
    public function withAddedHeader($name, $value) :self
    {
        return $this->new($this->psr7_response->withAddedHeader($name, $value));
    }
    
    /**
     * @return static
     */
    public function withoutHeader($name) :self
    {
        return $this->new($this->psr7_response->withoutHeader($name));
    }
    
    /**
     * @return static
     */
    public function withBody(StreamInterface $body) :self
    {
        return $this->new($this->psr7_response->withBody($body));
    }
    
    /**
     * @return static
     */
    public function withStatus($code, $reasonPhrase = '') :self
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