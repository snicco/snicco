<?php

declare(strict_types=1);

namespace Snicco\Events;

use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;

class ResponseSent extends Event
{
    
    public Response $response;
    public Request  $request;
    
    public function __construct(Response $response, Request $request)
    {
        $this->response = $response;
        $this->request = $request;
    }
    
}