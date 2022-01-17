<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

/**
 * @api
 */
trait ClassAsPayload
{
    
    public function getPayload()
    {
        return $this;
    }
    
}