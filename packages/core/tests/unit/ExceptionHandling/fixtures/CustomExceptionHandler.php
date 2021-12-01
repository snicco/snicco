<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Snicco\Http\Psr7\Request;
use Snicco\ExceptionHandling\ProductionExceptionHandler;

class CustomExceptionHandler extends ProductionExceptionHandler
{
    
    protected array $dont_report = [
        ReportableException::class,
    ];
    
    protected function globalContext(Request $request) :array
    {
        return ['foo' => 'bar'];
    }
    
}