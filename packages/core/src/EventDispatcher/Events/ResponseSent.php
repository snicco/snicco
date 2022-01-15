<?php

declare(strict_types=1);

namespace Snicco\Core\EventDispatcher\Events;

use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;

class ResponseSent extends CoreEvent
{
    
    public Response $response;
    public Request  $request;
    
    public function __construct(Response $response, Request $request)
    {
        $this->response = $response;
        $this->request = $request;
    }
    
}