<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Exception;

use LogicException;
use Psr\Http\Server\MiddlewareInterface;

use function implode;

final class InvalidMiddleware extends LogicException
{

    public static function incorrectInterface(string $name): InvalidMiddleware
    {
        return new self(
            sprintf(
                'The middleware [%s] does not implement [%s]',
                $name,
                MiddlewareInterface::class
            )
        );
    }

    public static function becauseTheAliasDoesNotExist(string $alias): InvalidMiddleware
    {
        return new self(
            sprintf(
                'The middleware alias [%s] does not exist.',
                $alias,
            )
        );
    }

    /**
     * @param array<string> $build_trace
     */
    public static function becauseRecursionWasDetected(array $build_trace, string $first_duplicate): InvalidMiddleware
    {
        $collapsed = implode('->', $build_trace) . '->' . $first_duplicate;

        return new self("Detected middleware recursion: $collapsed");
    }

}