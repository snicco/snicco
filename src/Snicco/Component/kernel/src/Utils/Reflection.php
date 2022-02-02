<?php

declare(strict_types=1);

namespace Snicco\Component\Kernel\Utils;

use Closure;
use InvalidArgumentException;
use ReflectionClass;
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

/**
 * @psalm-internal Snicco
 */
final class Reflection
{

    public static function getParameterClassName(ReflectionParameter $parameter): ?string
    {
        $type = $parameter->getType();

        if (!$type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }

        return self::getTypeName($parameter, $type);
    }

    private static function getTypeName(ReflectionParameter $parameter, ReflectionNamedType $type): string
    {
        $name = $type->getName();

        if (!is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }

            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }

        return $name;
    }

    /**
     * @param object|class-string $class_or_object
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
        $reflection = self::getReflectionFunction($callable);

        if (null === $reflection) {
            return null;
        }

        $parameters = $reflection->getParameters();

        if (!count($parameters) || !$parameters[0] instanceof ReflectionParameter) {
            return null;
        }

        $param = $parameters[0];

        $type = $param->getType();

        return ($type instanceof ReflectionNamedType) ? $type->getName() : null;
    }

    /**
     * @param Closure|array{0: class-string, 1: string}|class-string $callable A string will be interpreted as [string::class,__construct]
     *
     * @throws ReflectionException
     * @psalm-suppress DocblockTypeContradiction
     */
    public static function getReflectionFunction($callable): ?ReflectionFunctionAbstract
    {
        if (is_string($callable) && class_exists($callable)) {
            return (new ReflectionClass($callable))->getConstructor();
        }

        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        if (!is_array($callable)) {
            throw new InvalidArgumentException(
                sprintf(
                    '$callback_or_class_name has to be one of [string,array,closure]. Got [%s].',
                    gettype($callable)
                )
            );
        }

        if ('__construct' === $callable[1] || 'construct' === $callable[1]) {
            return (new ReflectionClass($callable[0]))->getConstructor();
        }

        if (!isset($callable[0]) || !is_string($callable[0])) {
            throw new InvalidArgumentException(
                'The first key in $callback_or_class_name is expected to be a non-empty string.'
            );
        }
        if (!isset($callable[1]) || !is_string($callable[1])) {
            throw new InvalidArgumentException(
                'The second key in $callback_or_class_name is expected to be a non-empty string.'
            );
        }

        return new ReflectionMethod($callable[0], $callable[1]);
    }

}