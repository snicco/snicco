<?php

declare(strict_types=1);

namespace Snicco\Component\Core\Utils;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use ReflectionParameter;
use ReflectionNamedType;
use ReflectionException;
use InvalidArgumentException;
use ReflectionFunctionAbstract;

/**
 * @framework-only
 */
final class Reflection
{
    
    public static function getParameterClassName(ReflectionParameter $parameter) :?string
    {
        $type = $parameter->getType();
        
        if ( ! $type instanceof ReflectionNamedType || $type->isBuiltin()) {
            return null;
        }
        
        return self::getTypeName($parameter, $type);
    }
    
    /**
     * @param  Closure|array|string  $callable  A string will be interpreted as
     *     [string::class,__construct]
     *
     * @throws ReflectionException
     */
    public static function getReflectionFunction($callable) :?ReflectionFunctionAbstract
    {
        if (is_string($callable) && class_exists($callable)) {
            return (new ReflectionClass($callable))->getConstructor();
        }
        
        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }
        
        if ( ! is_array($callable)) {
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
        
        if ( ! isset($callable[0]) || ! is_string($callable[0])) {
            throw new InvalidArgumentException(
                'The first key in $callback_or_class_name is expected to be a non-empty string.'
            );
        }
        if ( ! isset($callable[1]) || ! is_string($callable[1])) {
            throw new InvalidArgumentException(
                'The second key in $callback_or_class_name is expected to be a non-empty string.'
            );
        }
        
        return new ReflectionMethod($callable[0], $callable[1]);
    }
    
    /**
     * @param  Closure|array|string  $callable  A string will be interpreted as
     *     [string::class,__construct]
     *
     * @throws ReflectionException
     */
    public static function firstParameterType($callable) :?string
    {
        $reflection = self::getReflectionFunction($callable);
        
        if (null === $reflection) {
            return null;
        }
        
        $parameters = $reflection->getParameters();
        
        if ( ! count($parameters) || ! $parameters[0] instanceof ReflectionParameter) {
            return null;
        }
        
        $param = $parameters[0];
        
        $type = $param->getType();
        
        return $type ? $type->getName() : null;
    }
    
    private static function getTypeName(ReflectionParameter $parameter, ReflectionNamedType $type) :string
    {
        $name = $type->getName();
        
        if ( ! is_null($class = $parameter->getDeclaringClass())) {
            if ($name === 'self') {
                return $class->getName();
            }
            
            if ($name === 'parent' && $parent = $class->getParentClass()) {
                return $parent->getName();
            }
        }
        
        return $name;
    }
    
}