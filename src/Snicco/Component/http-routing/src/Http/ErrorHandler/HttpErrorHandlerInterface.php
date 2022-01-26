<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Http\ErrorHandler;

use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface HttpErrorHandlerInterface
{
    
    public function handle(Throwable $e, RequestInterface $request) :ResponseInterface;
    
}