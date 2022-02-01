<?php

declare(strict_types=1);

namespace Snicco\Component\Psr7ErrorHandler;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * @api
 */
interface HttpErrorHandlerInterface
{

    public function handle(Throwable $e, RequestInterface $request): ResponseInterface;

}