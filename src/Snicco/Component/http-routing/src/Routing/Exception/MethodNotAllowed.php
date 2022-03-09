<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Routing\Exception;

use Snicco\Component\Psr7ErrorHandler\HttpException;

final class MethodNotAllowed extends HttpException
{
    /**
     * @param string[] $allowed_methods
     */
    public static function currentMethod(string $method, array $allowed_methods, string $path): MethodNotAllowed
    {
        return new self(
            405,
            sprintf(
                '[%s] requests are not allowed for endpoint [%s]. Request method must be one of [%s].',
                $method,
                $path,
                implode(',', $allowed_methods)
            )
        );
    }
}
