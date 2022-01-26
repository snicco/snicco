<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler;

use Throwable;

interface Identifier
{
    
    /**
     * This method MUST be pure, meaning that two calls with the same object MUST return the same
     * ID.
     * Each identifier MUST be unique to the exception object.
     */
    public function identify(Throwable $e) :string;
    
}