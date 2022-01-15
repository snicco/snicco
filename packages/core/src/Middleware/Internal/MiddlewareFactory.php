<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use Closure;
use LogicException;
use Snicco\Core\DIContainer;
use InvalidArgumentException;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Core\Http\AbstractMiddleware;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Container\ContainerExceptionInterface;

use function Snicco\Core\Utils\isInterface;

/**
 * @internal
 */
final class MiddlewareFactory
{
    
    private DIContainer $container;
    
    public function __construct(DIContainer $container)
    {
        $this->container = $container;
    }
    
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function create(string $middleware_class, array $route_arguments = []) :MiddlewareInterface
    {
        if ( ! isInterface($middleware_class, MiddlewareInterface::class)) {
            throw new InvalidArgumentException(
                "Middleware [$middleware_class] has to be an instance of ["
                .MiddlewareInterface::class
                ."]."
            );
        }
        
        if ($this->container->has($middleware_class)) {
            $middleware = $this->container->get($middleware_class);
            if ($middleware instanceof Closure) {
                $middleware = $middleware(...array_values($route_arguments));
            }
            if ( ! $middleware instanceof MiddlewareInterface) {
                throw new LogicException(
                    sprintf(
                        "Resolving a middleware from the container must return an instance of [%s].\nGot [%s]",
                        MiddlewareInterface::class,
                        gettype($middleware)
                    )
                );
            }
        }
        else {
            $middleware = new $middleware_class(...array_values($route_arguments));
        }
        
        if ($middleware instanceof AbstractMiddleware) {
            $middleware->setContainer($this->container);
        }
        return $middleware;
    }
    
}