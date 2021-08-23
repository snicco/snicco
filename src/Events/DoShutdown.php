<?php

namespace Snicco\Events;

use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;

class DoShutdown extends Event
{
    
    private Request  $request;
    private Response $response;
    
    public function __construct(Request $request, Response $response)
    {
        
        $this->request = $request;
        $this->response = $response;
    }
    
    public function Request() :Request
    {
        return $this->request;
    }
    
    public function Response() :Response
    {
        return $this->response;
    }
    
    public function default() :bool
    {
        return true;
    }
    
}