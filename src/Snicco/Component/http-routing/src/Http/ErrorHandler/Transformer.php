<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler;

use Throwable;

interface Transformer
{
    
    public function transform(Throwable $e) :Throwable;
    
}