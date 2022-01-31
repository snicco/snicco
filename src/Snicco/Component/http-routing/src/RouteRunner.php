<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;

/**
 * @internal
 */
final class RouteRunner extends AbstractMiddleware
{

    private MiddlewarePipeline $pipeline;
    private MiddlewareStack $middleware_stack;
    private ContainerInterface $container;

    public function __construct(
        MiddlewarePipeline $pipeline,
        MiddlewareStack $middleware_stack,
        ContainerInterface $container
    ) {
        $this->pipeline = $pipeline;
        $this->middleware_stack = $middleware_stack;
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
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

        $route_middleware = array_merge($route->getMiddleware(), $action->getMiddleware());

        $middleware = $this->middleware_stack->createWithRouteMiddleware($route_middleware);

        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function (Request $request) use ($result, $action) {
                $response = $action->execute(
                    $request,
                    array_merge(
                        $result->decodedSegments(),
                        $result->route()->getDefaults()
                    )
                );

                return $this->respond()->toResponse($response);
            });
    }

    private function delegate(Request $request): Response
    {
        $middleware = $this->middleware_stack->createForRequestWithoutRoute($request);

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