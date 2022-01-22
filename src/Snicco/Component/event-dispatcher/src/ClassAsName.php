<?php

declare(strict_types=1);

namespace Snicco\Component\EventDispatcher;

/**
 * @api
 */
trait ClassAsName
{
    
    public function getName() :string
    {
        return static::class;
    }
    
}