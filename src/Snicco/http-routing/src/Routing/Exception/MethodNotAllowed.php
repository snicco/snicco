<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Routing\Exception;

use Snicco\Core\ExceptionHandling\Exceptions\HttpException;

/**
 * @api
 */
final class MethodNotAllowed extends HttpException
{
    
    public static function currentMethod(string $method, array $allowed_methods, string $path) :MethodNotAllowed
    {
        return new self(
            405, sprintf(
                "[%s] requests are not allowed for endpoint [%s]. Request method must be one of [%s].",
                $method,
                $path,
                implode(',', $allowed_methods)
            )
        );
    }
    
}