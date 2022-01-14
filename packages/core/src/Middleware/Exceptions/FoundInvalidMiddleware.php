<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Exceptions;

use LogicException;
use Psr\Http\Server\MiddlewareInterface;

final class FoundInvalidMiddleware extends LogicException
{
    
    public static function incorrectInterface(string $name) :FoundInvalidMiddleware
    {
        return new self(
            sprintf(
                "The middleware [%s] does not implement [%s]",
                $name,
                MiddlewareInterface::class
            )
        );
    }
    
    public static function becauseTheAliasDoesNotExist(string $alias) :FoundInvalidMiddleware
    {
        return new self(
            sprintf(
                'The middleware alias [%s] does not exist.',
                $alias,
            )
        );
    }
    
}