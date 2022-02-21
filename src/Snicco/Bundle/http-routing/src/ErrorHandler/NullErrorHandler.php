<?php

declare(strict_types=1);


namespace Snicco\Bundle\HttpRouting\ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Throwable;

/**
 * @note This ErrorHandler should only be used during testing.
 */
final class NullErrorHandler implements HttpErrorHandlerInterface
{

    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        throw $e;
    }
}