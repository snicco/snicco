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

use function class_exists;
use function class_implements;
use function in_array;
use function interface_exists;
use function is_string;
use function sprintf;

/**
 * @interal
 * @psalm-internal Snicco\Component\HttpRouting
 */
final class Reflector
{
    /**
     * @template Interface
     *
     * @psalm-assert class-string<Interface> $class_string
     *
     * @param class-string|string     $class_string
     * @param class-string<Interface> $expected_interface
     *
     * @throws InvalidArgumentException
     */
    public static function assertInterfaceString(
        string $class_string,
        string $expected_interface,
        string $message = null
    ): void {
        $class_exists = class_exists($class_string);
        $interface_exists = interface_exists($expected_interface);

        if (! $interface_exists) {
            throw new InvalidArgumentException(sprintf('Interface [%s] does not exist.', $expected_interface));
        }

        if (! $class_exists) {
            throw new InvalidArgumentException(
                sprintf($message ?: "Expected class-string<%s>\nGot: [%s].", $expected_interface, $class_string)
            );
        }

        $implements = (array) class_implements($class_string);

        if (in_array($expected_interface, $implements, true)) {
            return;
        }

        throw new InvalidArgumentException(
            sprintf($message ?: "Expected class-string<%s>\nGot: [%s].", $expected_interface, $class_string)
        );
    }

    /**
     * @param array{0: class-string|object, 1: string}|class-string|Closure $callable
     *
     * @throws ReflectionException
     */
    public static function firstParameterType($callable): ?string
    {
        $reflection = self::reflectionFunction($callable);

        $parameters = $reflection->getParameters();
        if ([] === $parameters) {
            return null;
        }

        $param = $parameters[0];

        $type = $param->getType();

        return ($type instanceof ReflectionNamedType) ? $type->getName() : null;
    }

    /**
     * @param array{0: class-string|object, 1: string}|class-string|Closure $callable
     *
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
