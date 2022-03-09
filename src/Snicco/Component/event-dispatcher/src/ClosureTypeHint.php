<?php

declare(strict_types=1);


namespace Snicco\Component\EventDispatcher;

use Closure;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use Snicco\Component\EventDispatcher\Exception\InvalidListener;

use function count;

/**
 * @psalm-internal Snicco\Component\EventDispatcher
 *
 * @interal
 */
final class ClosureTypeHint
{
    /**
     * @throws ReflectionException
     */
    public static function first(Closure $closure): string
    {
        $reflection = new ReflectionFunction($closure);

        $parameters = $reflection->getParameters();

        if (!count($parameters) || !$parameters[0] instanceof ReflectionParameter) {
            throw InvalidListener::becauseTheClosureDoesntHaveATypeHintedObject();
        }

        $param = $parameters[0];

        $type = $param->getType();

        if (!$type instanceof ReflectionNamedType) {
            throw InvalidListener::becauseTheClosureDoesntHaveATypeHintedObject();
        }

        return $type->getName();
    }
}
