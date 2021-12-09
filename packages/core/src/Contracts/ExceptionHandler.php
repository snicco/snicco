<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use Throwable;
use Psr\Log\LogLevel;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;

interface ExceptionHandler
{
    
    public function toHttpResponse(Throwable $e, Request $request) :Response;
    
    public function report(Throwable $e, Request $request, string $psr3_log_level = LogLevel::ERROR);
    
}
