<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Exception;

class ReportableException extends Exception
{
    
    public function report()
    {
        $GLOBALS['test']['log'][] = $this->getMessage();
    }
    
}