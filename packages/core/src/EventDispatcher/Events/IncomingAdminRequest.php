<?php

declare(strict_types=1);

namespace Snicco\Core\EventDispatcher\Events;


class IncomingAdminRequest extends IncomingRequest
{
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}