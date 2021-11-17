<?php

declare(strict_types=1);

namespace Snicco\EventDispatcher\Contracts;

interface CustomizablePayload
{
    
    public function payload() :array;
    
}