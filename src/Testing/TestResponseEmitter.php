<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Snicco\Http\Cookies;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Http\ResponseEmitter;

class TestResponseEmitter extends ResponseEmitter
{
    
    public ?TestResponse $response = null;
    
    public Cookies $cookies;
    
    public bool $sent_headers = false;
    
    public bool $sent_body = false;
    
    public function emit(Response $response) :void
    {
        $this->response = new TestResponse($response);
        $this->sent_headers = true;
        $this->sent_body = true;
    }
    
    public function emitCookies(Cookies $cookies) :void
    {
        $this->cookies = $cookies;
    }
    
    public function emitHeaders(Response $response, ?Request $request = null) :void
    {
        $this->response = new TestResponse($response);
        $this->sent_headers = true;
    }
    
}