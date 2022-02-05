<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;

use function array_unshift;
use function array_values;
use function call_user_func_array;

/**
 * @interal
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class ControllerAction
{

    private ContainerInterface $container;

    /**
     * @var array{0: class-string, 1:string} $class_callable
     */
    private array $class_callable;

    private object $controller_instance;

    /**
     * @param array{0: class-string, 1:string} $class_callable
     */
    public function __construct(array $class_callable, ContainerInterface $container)
    {
        $this->class_callable = $class_callable;
        $this->container = $container;
    }

    /**
     * @return mixed
     *
     * @throws ReflectionException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @return string[]
     *
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    public function middleware(): array
    {
        return $this->resolveControllerMiddleware();
    }

    /**
     * @return string[]
     * @throws ReflectionException
     *
     * @throws ContainerExceptionInterface
     */
    private function resolveControllerMiddleware(): array
    {
        $this->controller_instance = $this->instantiateController($this->class_callable[0]);

        if (!$this->controller_instance instanceof AbstractController) {
            return [];
        }

        return $this->controller_instance->getMiddleware($this->class_callable[1]);
    }

    /**
     * @param class-string $class
     *
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     *
     * @psalm-suppress MixedReturnStatement
     * @psalm-suppress MixedInferredReturnType
     */
    private function instantiateController(string $class): object
    {
        try {
            return $this->container->get($class);
        } catch (NotFoundExceptionInterface $e) {
            return (new ReflectionClass($class))->newInstance();
        }
    }

}