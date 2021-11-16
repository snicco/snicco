<?php

declare(strict_types=1);

namespace Snicco\ExceptionHandling;

use Throwable;
use Psr\Log\LogLevel;
use Snicco\Http\Psr7\Request;
use Snicco\Http\Psr7\Response;
use Snicco\Contracts\ExceptionHandlerInterface;

class NullExceptionHandler implements ExceptionHandlerInterface
{
    
    public function report(Throwable $e, Request $request, string $psr3_log_level = LogLevel::ERROR)
    {
        //
    }
    
    public function toHttpResponse(Throwable $e, Request $request) :Response
    {
        throw $e;
    }
    
}