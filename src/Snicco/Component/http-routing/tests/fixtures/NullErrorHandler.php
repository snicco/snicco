<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures;

use Throwable;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;

final class NullErrorHandler implements HttpErrorHandlerInterface
{
    
    /**
     * @throws Throwable
     */
    public function handle(Throwable $e, RequestInterface $request) :ResponseInterface
    {
        throw $e;
    }
    
}