<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\ExceptionHandling\ProductionExceptionHandler;

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