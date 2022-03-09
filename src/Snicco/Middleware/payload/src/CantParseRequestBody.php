<?php

declare(strict_types=1);

namespace Snicco\Middleware\Payload;

use Snicco\Component\Psr7ErrorHandler\HttpException;
use Throwable;

final class CantParseRequestBody extends HttpException
{
    public function __construct(string $message, Throwable $previous = null)
    {
        parent::__construct(
            400,
            $message,
            [],
            0,
            $previous
        );
    }
}
