<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use Snicco\Component\HttpRouting\Controller\ControllerAction;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Response\DelegatedResponse;
use Snicco\Component\HttpRouting\Routing;
use Snicco\Component\HttpRouting\Routing\Route\Route;

final class RouteRunner extends Middleware
{
    private MiddlewarePipeline $pipeline;

    private MiddlewareResolver $middleware_resolver;

    private ContainerInterface $container;

    public function __construct(
        MiddlewarePipeline $pipeline,
        MiddlewareResolver $middleware_resolver,
        ContainerInterface $container
    ) {
        $this->pipeline = $pipeline;
        $this->middleware_resolver = $middleware_resolver;
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws ReflectionException
     *
     * @psalm-suppress MixedAssignment
     * @psalm-suppress MixedArgument
     */
    protected function handle(Request $request, NextMiddleware $next): ResponseInterface
    {
        $result = $request->routingResult();
        $route = $result->route();

        if (! $route instanceof Route) {
            return $this->delegate($request);
        }

        $action = new ControllerAction($route->getController());

        $middleware = $this->middleware_resolver->resolveForRoute($route, $action);

        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(function (Request $request) use ($result, $action): Response {
                $segments = $result->decodedSegments();
                /** @var Routing\Route\Route $route */
                $route = $result->route();

                $response = $action->execute(
                    $request,
                    array_merge($segments, $route->getDefaults()),
                    $this->container
                );

                return $this->responseFactory()
                    ->toResponse($response);
            });
    }

    private function delegate(Request $request): Response
    {
        $middleware = $this->middleware_resolver->resolveForRequestWithoutRoute($request);

        if ([] === $middleware) {
            return $this->responseFactory()
                ->delegate();
        }

        return $this->pipeline
            ->send($request)
            ->through($middleware)
            ->then(fn (): DelegatedResponse => $this->responseFactory()->delegate());
    }
}
