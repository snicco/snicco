<?php

declare(strict_types=1);

namespace Tests\Core\unit\ExceptionHandling\fixtures;

use Throwable;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\ExceptionHandling\Exceptions\HttpException;
use Snicco\Core\ExceptionHandling\ProductionExceptionHandler;

class TestExceptionHandler extends ProductionExceptionHandler
{
    
    protected function toHttpException(Throwable $e, Request $request) :HttpException
    {
        return (new HttpException(500, 'Custom Error Message'))->withMessageForUsers(
            'Custom Error Message'
        );
    }
    
}