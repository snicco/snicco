<?php

declare(strict_types=1);

namespace Snicco\Testing;

use Snicco\Component\HttpRouting\Http\Cookies;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

class TestResponseEmitter
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