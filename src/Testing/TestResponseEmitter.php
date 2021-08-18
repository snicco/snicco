<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Snicco\Http\Cookies;
use Snicco\Http\ResponseEmitter;
use Psr\Http\Message\ResponseInterface;

class TestResponseEmitter extends ResponseEmitter
{
    
    public ?TestResponse $response = null;
    public Cookies       $cookies;
    
    public function emit(ResponseInterface $response) :void
    {
        
        $this->response = new TestResponse($response);
    }
    
    public function emitCookies(Cookies $cookies)
    {
        
        $this->cookies = $cookies;
    }
    
    public function emitHeaders(ResponseInterface $response) :void
    {
        //
    }
    
}