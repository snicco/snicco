<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

/**
 * @api
 */
trait ClassAsPayload
{
    
    public function payload() :self
    {
        return $this;
    }
    
}