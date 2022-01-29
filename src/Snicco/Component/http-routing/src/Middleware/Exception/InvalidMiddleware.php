<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware\Exception;

use LogicException;
use Psr\Http\Server\MiddlewareInterface;

final class InvalidMiddleware extends LogicException
{
    
    public static function incorrectInterface(string $name) :InvalidMiddleware
    {
        return new self(
            sprintf(
                "The middleware [%s] does not implement [%s]",
                $name,
                MiddlewareInterface::class
            )
        );
    }
    
    public static function becauseTheAliasDoesNotExist(string $alias) :InvalidMiddleware
    {
        return new self(
            sprintf(
                'The middleware alias [%s] does not exist.',
                $alias,
            )
        );
    }
    
}