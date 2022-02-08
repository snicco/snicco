<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Closure;
use InvalidArgumentException;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

use function class_exists;
use function class_implements;
use function get_class;
use function in_array;
use function interface_exists;
use function is_object;
use function is_string;

/**
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class Reflection
{

    /**
     * @param object|string $class_or_object
     * @param class-string $interface
     *
     * @throws InvalidArgumentException if the interface does not exist
     */
    public static function isInterface($class_or_object, string $interface): bool
    {
        $class = is_object($class_or_object)
            ? get_class($class_or_object)
            : $class_or_object;

        $interface_exists = interface_exists($interface);

        if (false === $interface_exists) {
            throw new InvalidArgumentException("Interface [$interface] does not exist.");
        }

        if ($interface === $class) {
            return true;
        }

        if (!class_exists($class) && !interface_exists($class)) {
            return false;
        }

        $implements = (array)class_implements($class);

        return in_array($interface, $implements, true);
    }

    /**
     * @param Closure|array{0: class-string, 1: string}|class-string $callable
     *
     * @throws ReflectionException
     */
    public static function firstParameterType($callable): ?string
    {
        $reflection = self::reflectionFunction($callable);

        $parameters = $reflection->getParameters();

        if (!count($parameters) || !$parameters[0] instanceof ReflectionParameter) {
            return null;
        }

        $param = $parameters[0];

        $type = $param->getType();

        return ($type instanceof ReflectionNamedType) ? $type->getName() : null;
    }

    /**
     * @param Closure|array{0: class-string, 1: string}|class-string $callable
     * @throws ReflectionException
     */
    private static function reflectionFunction($callable): ReflectionFunctionAbstract
    {
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }
        if (is_string($callable)) {
            return new ReflectionMethod($callable, '__construct');
        }

        return new ReflectionMethod($callable[0], $callable[1]);
    }

}