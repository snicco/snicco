<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Throwable;
use Snicco\Http\Psr7\Request;
use Snicco\Contracts\ExceptionHandler;

class NullExceptionHandler implements ExceptionHandler
{
    
    public function render(Throwable $e, Request $request)
    {
        throw $e;
    }
    
    public function report(Throwable $e, Request $request)
    {
        throw $e;
    }
    
}