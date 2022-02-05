<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\fixtures;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Component\Psr7ErrorHandler\HttpErrorHandlerInterface;
use Throwable;

final class NullErrorHandler implements HttpErrorHandlerInterface
{

    /**
     * @throws Throwable
     */
    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface
    {
        throw $e;
    }

}