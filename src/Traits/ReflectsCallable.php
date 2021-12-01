<?php

declare(strict_types=1);

namespace Snicco\Traits;

use Closure;
use ReflectionClass;
use ReflectionMethod;
use ReflectionFunction;
use Snicco\Support\Str;
use ReflectionException;
use ReflectionParameter;
use InvalidArgumentException;
use ReflectionFunctionAbstract;

trait ReflectsCallable
{
    
    /**
     * Accepts a string that contains and @ and returns the part before the @.
     *
     * @param $object
     *
     * @return bool
     */
    protected function isClosure($object) :bool
    {
        return $object instanceof Closure;
    }
    
    /**
     * @param  array|Closure|string  $callback_or_class_name
     * @param  string  $default_method
     *
     * @return ReflectionFunctionAbstract
     * @throws ReflectionException
     */
    private function getCallReflector($callback_or_class_name, string $default_method = '') :?ReflectionFunctionAbstract
    {
        if (is_string($callback_or_class_name) && class_exists($callback_or_class_name)) {
            return (new ReflectionClass($callback_or_class_name))->getConstructor();
        }
        
        if ($this->isClosure($callback_or_class_name)) {
            return new ReflectionFunction($callback_or_class_name);
        }
        
        [$class, $method] = ($this->classExists($callback_or_class_name[0]))
            ? [$callback_or_class_name[0], $callback_or_class_name[1] ?? $default_method]
            : Str::parseCallback($callback_or_class_name[0], $default_method);
        
        return new ReflectionMethod($class, $method);
    }
    
    /**
     * @param $class_name_or_object
     *
     * @return bool
     */
    private function classExists($class_name_or_object) :bool
    {
        if (is_object($class_name_or_object)) return true;
        
        return class_exists($class_name_or_object);
    }
    
    private function firstClosureParameterType(Closure $closure) :string
    {
        $reflection = new ReflectionFunction($closure);
        
        $parameters = (array) $reflection->getParameters();
        
        if ( ! count($parameters) || ! $parameters[0] instanceof ReflectionParameter) {
            throw new InvalidArgumentException(
                "Closure does not have first parameter typehinted."
            );
        }
        
        $param = $parameters[0];
        
        $type = $param->getType();
        
        return $type->getName();
    }
    
}