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
use function in_array;
use function interface_exists;
use function is_string;

/**
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class Reflection
{

    /**
     * @template Interface
     *
     * @psalm-assert-if-true class-string<Interface> $class_string
     *
     * @param string|class-string $class_string
     * @param class-string<Interface> $interface
     *
     * @throws InvalidArgumentException if the interface does not exist
     */
    public static function isInterfaceString(string $class_string, string $interface): bool
    {
        $class_exists = class_exists($class_string);
        $interface_exists = interface_exists($interface);

        if (false === $interface_exists) {
            throw new InvalidArgumentException("Interface [$interface] does not exist.");
        }

        if ($interface === $class_string) {
            return true;
        }

        if (!$class_exists && !interface_exists($class_string)) {
            return false;
        }

        $implements = (array)class_implements($class_string);

        return in_array($interface, $implements, true);
    }

    /**
     * @param Closure|class-string|array{0: class-string|object, 1: string} $callable
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
     * @param Closure|class-string|array{0: class-string|object, 1: string} $callable
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