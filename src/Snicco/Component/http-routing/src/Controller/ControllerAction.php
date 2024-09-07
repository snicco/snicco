<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use ReflectionException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Reflector;

use Webmozart\Assert\Assert;

use function array_merge;
use function array_unshift;
use function array_values;
use function call_user_func;
use function call_user_func_array;
use function class_parents;
use function in_array;

/**
 * @psalm-internal Snicco\Component\HttpRouting
 *
 * @interal
 */
final class ControllerAction
{
    /**
     * @var class-string
     */
    private string $controller_class;

    private string $controller_method;

    /**
     * @param array{0: class-string, 1:string} $class_callable
     */
    public function __construct(array $class_callable)
    {
        [$class, $method] = $class_callable;
        $this->controller_class = $class;
        $this->controller_method = $method;
    }

    /**
     * @throws ReflectionException
     *
     * @return mixed
     */
    public function execute(Request $request, array $captured_args_decoded, ContainerInterface $container)
    {
        $instance = $this->instantiateController($this->controller_class, $container);

        $callable = [$instance, $this->controller_method];

        if (Request::class === Reflector::firstParameterType($callable)) {
            array_unshift($captured_args_decoded, $request);
        }

        if ($instance instanceof Controller) {
            $instance->setCurrentRequest($request);
        }

        return call_user_func_array($callable, array_values($captured_args_decoded));
    }

    /**
     * @psalm-mutation-free
     *
     * @return class-string<MiddlewareInterface>[]
     */
    public function middleware(): array
    {
        $class = $this->controller_class;

        $parents = class_parents($class);
        if (false === $parents || ! in_array(Controller::class, $parents, true)) {
            return [];
        }

        $middleware_for_method = [];

        foreach (call_user_func([$class, 'middleware']) as $controller_middleware) {
            Assert::isInstanceOf(
                $controller_middleware,
                ControllerMiddleware::class,
                "Controller {$this->controller_class}::middleware must return ControllerMiddleware::class"
            );

            if ($controller_middleware->appliesTo($this->controller_method)) {
                $middleware_for_method = array_merge($middleware_for_method, $controller_middleware->toArray());
            }
        }

        return $middleware_for_method;
    }

    /**
     * @param class-string $class
     *
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     */
    private function instantiateController(string $class, ContainerInterface $container): object
    {
        try {
            /** @var object $instance */
            $instance = $container->get($class);
        } catch (NotFoundExceptionInterface $e) {
            $instance = (new ReflectionClass($class))->newInstance();
        }

        if ($instance instanceof Controller) {
            $instance->setContainer($container);
        }

        return $instance;
    }
}
