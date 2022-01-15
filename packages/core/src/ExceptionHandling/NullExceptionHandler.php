<?php

declare(strict_types=1);

namespace Snicco\Core\ExceptionHandling;

use Throwable;
use Psr\Log\LogLevel;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;

class NullExceptionHandler implements ExceptionHandler
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