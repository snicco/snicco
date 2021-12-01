<?php

declare(strict_types=1);

namespace Snicco\Routing;

use Snicco\Http\Controller;
use Snicco\View\ViewEngine;
use Snicco\Shared\ContainerAdapter;
use Snicco\Http\ResponseFactory;
use Snicco\Contracts\RouteAction;
use Snicco\Support\ReflectionDependencies;
use Snicco\View\Contracts\ViewFactoryInterface;

class ControllerAction implements RouteAction
{
    
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
                      ?? $this->container->make($this->class_callable[0]);
        
        if ($controller instanceof Controller) {
            $controller->giveResponseFactory($this->container->make(ResponseFactory::class));
            $controller->giveUrlGenerator($this->container->make(UrlGenerator::class));
            $controller->giveViewEngine($this->container->make(ViewEngine::class));
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
        
        $this->controller_instance = $this->container->make($this->class_callable[0]);
        
        return $this->controller_instance->getMiddleware($this->class_callable[1]);
    }
    
}