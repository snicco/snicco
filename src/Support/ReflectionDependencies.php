<?php

declare(strict_types=1);

namespace Snicco\Support;

use Closure;
use ReflectionException;
use ReflectionParameter;
use Contracts\ContainerAdapter;
use Illuminate\Support\Reflector;
use Snicco\Traits\ReflectsCallable;

use function collect;

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
        $reflection_method = $this->getCallReflector($route_action);
        
        if ( ! $reflection_method) {
            return [];
        }
        
        $params = $reflection_method->getParameters();
        if ( ! count($params)) {
            return $parsed_parameters;
        }
        
        if ( ! $this->firstParameterIsClass($params)) {
            return $this->onlyPrimitives($parsed_parameters);
        }
        
        foreach ($params as $reflection_parameter) {
            if ( ! $class_name = Reflector::getParameterClassName($reflection_parameter)) {
                break;
            }
            
            if ($class = $this->classInParameters($class_name, $parsed_parameters)) {
                $route_dependencies[] = $class;
                continue;
            }
            
            $route_dependencies[] = $reflection_parameter->isDefaultValueAvailable()
                ? $reflection_parameter->getDefaultValue()
                : $this->container->make($class_name);
        }
        
        return array_merge($route_dependencies, $this->onlyPrimitives($parsed_parameters));
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
    
    private function onlyPrimitives(array $args) :array
    {
        return collect($args)
            ->reject(fn($value) => is_object($value))
            ->values()
            ->all();
    }
    
}