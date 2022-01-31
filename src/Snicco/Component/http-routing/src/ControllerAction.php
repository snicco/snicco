<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use ReflectionException;
use Psr\Container\ContainerInterface;
use Snicco\Component\Core\Utils\Reflection;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

use function array_values;
use function array_unshift;
use function call_user_func_array;

/**
 * @interal
 */
final class ControllerAction
{
    
    private ContainerInterface $container;
    private array              $class_callable;
    private object             $controller_instance;
    
    public function __construct(array $class_callable, ContainerInterface $container)
    {
        $this->class_callable = $class_callable;
        $this->container = $container;
    }
    
    /**
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     */
    public function execute(Request $request, array $captured_args_decoded)
    {
        $controller = $this->controller_instance ?? $this->resolveControllerMiddleware();
        
        if ($controller instanceof AbstractController) {
            $controller->setContainer($this->container);
        }
        
        if (Reflection::firstParameterType($this->class_callable) === Request::class) {
            array_unshift($captured_args_decoded, $request);
        }
        
        return call_user_func_array(
            [$controller, $this->class_callable[1]],
            array_values($captured_args_decoded)
        );
    }
    
    /**
     * @throws ContainerExceptionInterface
     */
    public function getMiddleware() :array
    {
        return $this->resolveControllerMiddleware();
    }
    
    /**
     * @throws ContainerExceptionInterface
     */
    private function resolveControllerMiddleware() :array
    {
        $this->controller_instance = $this->instantiateController($this->class_callable[0]);
        
        if ( ! $this->controller_instance instanceof AbstractController) {
            return [];
        }
        
        return $this->controller_instance->getMiddleware($this->class_callable[1]);
    }
    
    /**
     * @throws ContainerExceptionInterface
     */
    private function instantiateController(string $class) :object
    {
        try {
            return $this->container->get($class);
        } catch (NotFoundExceptionInterface $e) {
            return new $class;
        }
    }
    
}