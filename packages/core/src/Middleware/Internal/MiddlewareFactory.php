<?php

declare(strict_types=1);

namespace Snicco\Core\Middleware\Internal;

use Closure;
use LogicException;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
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
    
    private ContainerInterface $container;
    
    public function __construct(ContainerInterface $container)
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
        
        try {
            $middleware = $this->container->get($middleware_class);
            
            if ($middleware instanceof Closure) {
                $middleware = $middleware(...array_values($route_arguments));
            }
            if ( ! $middleware instanceof MiddlewareInterface) {
                throw new LogicException(
                    sprintf(
                        "Resolving a middleware from the container must return an instance of [%s].\nGot [%s]",
                        MiddlewareInterface::class,
                        is_object($middleware) ? get_class($middleware) : gettype($middleware)
                    )
                );
            }
        } catch (NotFoundExceptionInterface $e) {
            // Don't check if the entry is in the container with has since many DI-containers
            // are capable of constructing the service with auto-wiring.
            $middleware = new $middleware_class(...array_values($route_arguments));
        }
        
        if ($middleware instanceof AbstractMiddleware) {
            $middleware->setContainer($this->container);
        }
        return $middleware;
    }
    
}