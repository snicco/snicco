<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher;

trait IsClassNameEvent
{
    
    public function getName() :string
    {
        return static::class;
    }
    
}