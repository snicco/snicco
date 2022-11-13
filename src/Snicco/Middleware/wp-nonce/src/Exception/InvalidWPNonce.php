<?php

declare(strict_types=1);

namespace Snicco\Middleware\WPNonce\Exception;

use Snicco\Component\Psr7ErrorHandler\HttpException;

use function sprintf;

final class InvalidWPNonce extends HttpException
{
    public static function forPath(string $request_path): self
    {
        return new self(
            403,
            sprintf('Nonce check failed for request path [%s].', $request_path)
        );
    }
}
