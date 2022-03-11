<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class TestErrorHandler implements HttpErrorHandler
{
    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        throw $e;
    }
}
