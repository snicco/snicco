<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface HttpErrorHandler
{
    public function handle(Throwable $e, ServerRequestInterface $request): ResponseInterface;
}
