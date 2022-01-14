<?php

declare(strict_types=1);

namespace Snicco\Core\Utils;

use LogicException;
use Snicco\Support\Arr;
use ReflectionParameter;
use ReflectionException;
use Webmozart\Assert\Assert;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

/**
 * This class builds an array of arguments for calling a closure or a method on a class where some
 * arguments are available and some arguments are class dependencies.
 * The array correct array of method arguments will be build for the callable and one requirement.
 * Class dependencies MUST be defined before primitive values in the callable.
 *
 * @framework-only
 */
final class ReflectionDependencies
{
    
    private ContainerInterface $container;
    
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @param  array<string,string>  $class_and_method
     * @param  array  $extra_arguments
     *
     * @return array
     * @throws ReflectionException
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function build(array $class_and_method, array $extra_arguments = []) :array
    {
        Assert::stringNotEmpty($class_and_method[0]);
        Assert::stringNotEmpty($class_and_method[1]);
        
        $dependencies = [];
        $added_instances = [];
        $reflection_method = Reflection::getReflectionFunction($class_and_method);
        
        if ( ! $reflection_method) {
            return $extra_arguments;
        }
        
        $params = $reflection_method->getParameters();
        
        if ( ! count($params)) {
            return $extra_arguments;
        }
        
        if ( ! $this->firstParameterIsClass($params)) {
            $this->verifyNoClassesBeingUsed($params, $class_and_method);
            
            return $this->onlyPrimitives($extra_arguments, []);
        }
        
        foreach ($params as $reflection_parameter) {
            $class_name = Reflection::getParameterClassName($reflection_parameter);
            
            if (null === $class_name) {
                break;
            }
            
            if ($class = $this->classInParameters($class_name, $extra_arguments)) {
                $dependencies[] = $class;
                $added_instances[] = get_class($class);
                continue;
            }
            
            if ($reflection_parameter->isDefaultValueAvailable()) {
                $dependencies[] = $reflection_parameter->getDefaultValue();
                continue;
            }
            
            $dependencies[] = $class = $this->container->get($class_name);
            $added_instances[] = get_class($class);
        }
        
        return array_merge(
            $dependencies,
            $this->onlyPrimitives($extra_arguments, $added_instances)
        );
    }
    
    private function classInParameters(string $class_name, array $parsed_parameters) :?object
    {
        return Arr::first($parsed_parameters, function ($value) use ($class_name) {
            return $value instanceof $class_name;
        });
    }
    
    /**
     * @param  ReflectionParameter[]  $reflection_params
     */
    private function firstParameterIsClass(array $reflection_params) :bool
    {
        return ! is_null(Reflection::getParameterClassName($reflection_params[0]));
    }
    
    private function onlyPrimitives(array $args, array $added_classes) :array
    {
        return array_filter($args, function ($value) use ($added_classes) {
            if (is_object($value) && in_array(get_class($value), $added_classes, true)) {
                return false;
            }
            return true;
        });
    }
    
    /**
     * @param  ReflectionParameter[]  $params
     *
     * @return void
     */
    private function verifyNoClassesBeingUsed(array $params, array $callable)
    {
        $first = $params[0];
        
        foreach ($params as $param) {
            if (null !== Reflection::getParameterClassName($param)) {
                throw new LogicException(
                    sprintf(
                        "Cant instantiate [%s].\nPrimitive parameter [%s] is defined before non-primitive parameter [%s].",
                        implode('::', $callable),
                        $first->getName(),
                        $param->getType()
                    )
                );
            }
        }
    }
    
}