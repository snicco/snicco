<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Closure;
use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

/**
 * @psalm-internal Snicco\Component\HttpRouting
 *
 * @interal
 */
final class MiddlewareFactory
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param class-string<MiddlewareInterface> $middleware_class
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function create(string $middleware_class, array $route_arguments = []): MiddlewareInterface
    {
        try {
            $middleware = $this->container->get($middleware_class);

            if ($middleware instanceof Closure) {
                /** @var mixed $middleware */
                $middleware = $middleware(...array_values($route_arguments));
            }

            if (! $middleware instanceof MiddlewareInterface) {
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

        if ($middleware instanceof Middleware) {
            $middleware->setContainer($this->container);
        }

        return $middleware;
    }
}
