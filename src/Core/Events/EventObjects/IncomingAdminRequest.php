<?php

declare(strict_types=1);

namespace Snicco\Core\Events\EventObjects;

class IncomingAdminRequest extends IncomingRequest
{
    
    public function shouldDispatch() :bool
    {
        return true;
    }
    
}