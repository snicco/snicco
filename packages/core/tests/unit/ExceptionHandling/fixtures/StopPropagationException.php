<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;
use Snicco\ExceptionHandling\ProductionExceptionHandler;

class StopPropagationException extends Exception
{
    
    public function report() :bool
    {
        $GLOBALS['test']['log'][] = $this->getMessage();
        
        return ProductionExceptionHandler::STOP_REPORTING;
    }
    
}