<?php

namespace Snicco\Core\EventDispatcher\Events;


use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\EventDispatcher\Contracts\Mutable;

class DoShutdown extends CoreEvent implements Mutable
{
    
    public Request  $request;
    public Response $response;
    public bool     $do_shutdown = true;
    
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
    
}