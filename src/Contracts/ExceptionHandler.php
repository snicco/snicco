<?php

declare(strict_types=1);

namespace Snicco\Contracts;

use Throwable;
use Snicco\Http\Psr7\Request;

interface ExceptionHandler
{
    
    public function render(Throwable $e, Request $request);
    
    public function report(Throwable $e, Request $request);
    
}
