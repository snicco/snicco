<?php

declare(strict_types=1);

namespace Snicco\Support;

use Closure;
use ReflectionException;
use ReflectionParameter;
use Snicco\Shared\ContainerAdapter;
use Snicco\Traits\ReflectsCallable;

/**
 * This class is used to build the dependencies for route actions, middleware, conditions and view
 * composers. Class dependencies will be resolved from the service container. All class
 * dependencies MUST come before any primitives in the method dependencies.
 *
 * @internal
 */
class ReflectionDependencies
{
    
    use ReflectsCallable;
    
    private ContainerAdapter $container;
    
    public function __construct(ContainerAdapter $container)
    {
        $this->container = $container;
    }
    
    /**
     * @note Class dependencies of a route action have to be defined before any primitive values.
     *
     * @param  string|array|Closure|callable  $route_action
     * @param  array  $parsed_parameters
     *
     * @return array
     * @throws ReflectionException
     */
    public function build($route_action, array $parsed_parameters = []) :array
    {
        $route_dependencies = [];
        $added_instances = [];
        $reflection_method = $this->getCallReflector($route_action);
        
        if ( ! $reflection_method) {
            return [];
        }
        
        $params = $reflection_method->getParameters();
        if ( ! count($params)) {
            return $parsed_parameters;
        }
        
        if ( ! $this->firstParameterIsClass($params)) {
            return $this->filterClasses($parsed_parameters, $added_instances);
        }
        
        foreach ($params as $reflection_parameter) {
            if ( ! $class_name = Reflector::getParameterClassName($reflection_parameter)) {
                break;
            }
            
            if ($class = $this->classInParameters($class_name, $parsed_parameters)) {
                $route_dependencies[] = $class;
                $added_instances[] = get_class($class);
                continue;
            }
            
            if ($reflection_parameter->isDefaultValueAvailable()) {
                $route_dependencies[] = $reflection_parameter->getDefaultValue();
                continue;
            }
            
            $route_dependencies[] = $class = $this->container->make($class_name);
            $added_instances[] = get_class($class);
        }
        
        return array_merge(
            $route_dependencies,
            $this->filterClasses($parsed_parameters, $added_instances)
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
        return ! is_null(Reflector::getParameterClassName($reflection_params[0]));
    }
    
    private function filterClasses(array $args, array $added_classes) :array
    {
        return array_filter($args, function ($value) use ($added_classes) {
            if (is_object($value) && in_array(get_class($value), $added_classes, true)) {
                return false;
            }
            return true;
        });
    }
    
}