<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use LogicException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\ResponseUtils;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\StrArr\Arr;

use Webmozart\Assert\Assert;

use function array_filter;
use function array_merge;
use function sprintf;

abstract class Controller
{
    /**
     * @var ControllerMiddleware[]
     */
    private array $middleware = [];

    private ContainerInterface $container;

    private ?Request $current_request = null;

    /**
     * @return class-string<MiddlewareInterface>[]
     *
     * @psalm-mutation-free
     * @psalm-internal Snicco\Component\HttpRouting
     */
    final public function getMiddleware(string $controller_method): array
    {
        $middleware = array_filter(
            $this->middleware,
            fn (ControllerMiddleware $m): bool => $m->appliesTo($controller_method)
        );

        $middleware_for_method = [];

        foreach ($middleware as $controller_middleware) {
            $middleware_for_method = array_merge($middleware_for_method, $controller_middleware->toArray());
        }

        return $middleware_for_method;
    }

    /**
     * @psalm-internal Snicco\Component\HttpRouting
     */
    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @psalm-internal Snicco\Component\HttpRouting
     */
    final public function setCurrentRequest(Request $request): void
    {
        $this->current_request = $request;
    }

    final protected function url(): UrlGenerator
    {
        try {
            $url = $this->container->get(UrlGenerator::class);
            Assert::isInstanceOf($url, UrlGenerator::class);

            return $url;
        } catch (ContainerExceptionInterface $e) {
            throw new LogicException(
                "The UrlGenerator is not bound correctly in the psr container.\nMessage: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    final protected function responseFactory(): ResponseFactory
    {
        try {
            $res = $this->container->get(ResponseFactory::class);
            Assert::isInstanceOf($res, ResponseFactory::class);

            return $res;
        } catch (ContainerExceptionInterface $e) {
            throw new LogicException(
                "The ResponseFactory is not bound correctly in the psr container.\nMessage: {$e->getMessage()}",
                (int) $e->getCode(),
                $e
            );
        }
    }

    final protected function respondWith(): ResponseUtils
    {
        return new ResponseUtils($this->url(), $this->responseFactory(), $this->currentRequest());
    }

    /**
     * @param class-string<MiddlewareInterface>|class-string<MiddlewareInterface>[] $middleware_names
     */
    final protected function addMiddleware($middleware_names): ControllerMiddleware
    {
        $middleware = new ControllerMiddleware(Arr::toArray($middleware_names));
        $this->middleware[] = $middleware;

        return $middleware;
    }

    private function currentRequest(): Request
    {
        if (! isset($this->current_request)) {
            throw new RuntimeException(sprintf('Current request not set on controller [%s]', static::class));
        }

        return $this->current_request;
    }
}
