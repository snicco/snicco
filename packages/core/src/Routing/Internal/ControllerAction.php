<?php

declare(strict_types=1);

namespace Snicco\Core\Routing\Internal;

use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\AbstractController;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Support\ReflectsCallable;
use Snicco\Core\Support\ReflectionDependencies;

use function array_filter;
use function method_exists;
use function call_user_func_array;

/**
 * @interal
 */
final class ControllerAction
{
    
    use ReflectsCallable;
    
    private array            $class_callable;
    private ContainerAdapter $container;
    private object           $controller_instance;
    
    public function __construct(array $class_callable, ContainerAdapter $container)
    {
        $this->class_callable = $class_callable;
        $this->container = $container;
    }
    
    public function execute(array $args)
    {
        $controller = $this->controller_instance ?? $this->container->get($this->class_callable[0]);
        
        if ($controller instanceof AbstractController) {
            $controller->setContainer($this->container);
        }
        
        if ($this->firstParameterType($this->class_callable) !== Request::class) {
            $args = array_filter($args, function ($value) {
                return ! $value instanceof Request;
            });
        }
        
        $deps = (new ReflectionDependencies($this->container))->build($this->class_callable, $args);
        
        return call_user_func_array(
            [$controller, $this->class_callable[1]],
            $deps
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