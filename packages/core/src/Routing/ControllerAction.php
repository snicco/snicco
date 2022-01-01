<?php

declare(strict_types=1);

namespace Snicco\Core\Routing;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Contracts\RouteAction;
use Snicco\Core\Http\AbstractController;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Traits\ReflectsCallable;
use Snicco\Core\Support\ReflectionDependencies;

class ControllerAction implements RouteAction
{
    
    use ReflectsCallable;
    
    private array                  $class_callable;
    private ContainerAdapter       $container;
    private object                 $controller_instance;
    private ReflectionDependencies $route_dependencies;
    
    public function __construct(array $class_callable, ContainerAdapter $container, ReflectionDependencies $action_dependencies)
    {
        $this->class_callable = $class_callable;
        $this->container = $container;
        $this->route_dependencies = $action_dependencies;
    }
    
    public function execute(array $args)
    {
        $controller = $this->controller_instance
                      ?? $this->container->get($this->class_callable[0]);
        
        if ($controller instanceof AbstractController) {
            $controller->setContainer($this->container);
        }
        
        if ($this->firstParameterType($this->class_callable) !== Request::class) {
            $args = array_filter($args, function ($value) {
                return ! $value instanceof Request;
            });
        }
        
        return call_user_func_array(
            [$controller, $this->class_callable[1]],
            $this->route_dependencies->build($this->class_callable, $args)
        );
    }
    
    public function getMiddleware() :array
    {
        return $this->resolveControllerMiddleware();
    }
    
    private function resolveControllerMiddleware() :array
    {
        if ( ! method_exists($this->class_callable[0], 'getMiddleware')) {
            return [];
        }
        
        $this->controller_instance = $this->container->get($this->class_callable[0]);
        
        return $this->controller_instance->getMiddleware($this->class_callable[1]);
    }
    
}