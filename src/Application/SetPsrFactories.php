<?php

declare(strict_types=1);

namespace Snicco\Application;

use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;

trait SetPsrFactories
{
    
    public function setServerRequestFactory(ServerRequestFactoryInterface $server_request_factory) :self
    {
        $this->container()
             ->instance(ServerRequestFactoryInterface::class, $server_request_factory);
        
        return $this;
    }
    
    public function setUriFactory(UriFactoryInterface $uri_factory) :self
    {
        $this->container()->instance(UriFactoryInterface::class, $uri_factory);
        
        return $this;
    }
    
    public function setUploadedFileFactory(UploadedFileFactoryInterface $file_factory) :self
    {
        $this->container()->instance(UploadedFileFactoryInterface::class, $file_factory);
        
        return $this;
    }
    
    public function setStreamFactory(StreamFactoryInterface $stream_factory) :self
    {
        $this->container()->instance(StreamFactoryInterface::class, $stream_factory);
        
        return $this;
    }
    
    public function setResponseFactory(ResponseFactoryInterface $response_factory) :self
    {
        $this->container()->instance(ResponseFactoryInterface::class, $response_factory);
        
        return $this;
    }
    
}