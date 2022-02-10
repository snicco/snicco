<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @internal
 */
final class RouteRunner extends AbstractMiddleware
{

    private MiddlewarePipeline $pipeline;
    private MiddlewareResolver $middleware_stack;
    private ContainerInterface $container;

    public function __construct(
        MiddlewarePipeline $pipeline,
        MiddlewareResolver $middleware_stack,
        ContainerInterface $container
    ) {
        $this->pipeline = $pipeline;
        $this->middleware_stack = $middleware_stack;
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    public function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $result = $request->routingResult();
        $route = $result->route();

        if (!$route) {
            return $this->delegate($request);
        }

        $action = new ControllerAction(
            $route->getController(),
            $this->container,
        );

        $middleware = $this->middleware_stack->resolveForRoute($route, $action);

        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function (Request $request) use ($result, $action) {
                $segments = $result->decodedSegments();
                /** @var Routing\Route\Route $route */
                $route = $result->route();

                $response = $action->execute(
                    $request,
                    array_merge(
                        $segments,
                        $route->getDefaults()
                    )
                );

                return $this->respond()->toResponse($response);
            });
    }

    private function delegate(Request $request): Response
    {
        $middleware = $this->middleware_stack->resolveForRequestWithoutRoute($request);

        if (!count($middleware)) {
            return $this->respond()->delegate(true);
        }

        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function () {
                return $this->respond()->delegate();
            });
    }

}