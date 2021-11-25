<?php

declare(strict_types=1);

namespace Snicco\Core\Events\EventObjects;

use Snicco\Http\Psr7\Request;
use Snicco\EventDispatcher\Contracts\MappedAction;
use Snicco\EventDispatcher\Contracts\DispatchesConditionally;

abstract class IncomingRequest extends CoreEvent implements MappedAction, DispatchesConditionally
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