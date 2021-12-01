<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Events;

use Snicco\Http\Psr7\Request;
use Snicco\EventDispatcher\Contracts\MappedAction;

abstract class IncomingRequest extends CoreEvent implements MappedAction
{
    
    protected Request $request;
    
    public function __construct(Request $request)
    {
        $this->request = $request;
    }
    
    public function getPayload() :Request
    {
        return $this->request;
    }
    
}