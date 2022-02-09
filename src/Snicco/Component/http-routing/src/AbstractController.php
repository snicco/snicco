<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Snicco\Component\StrArr\Arr;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_merge;

abstract class AbstractController
{

    /**
     * @var ControllerMiddleware[]
     */
    private array $middleware = [];
    
    private ContainerInterface $container;

    /**
     * @interal
     * @return class-string<MiddlewareInterface>[]
     */
    public function middleware(string $controller_method): array
    {
        $middleware = array_filter(
            $this->middleware,
            fn(ControllerMiddleware $m) => $m->appliesTo($controller_method)
        );

        $middleware_for_method = [];

        foreach ($middleware as $controller_middleware) {
            $middleware_for_method = array_merge(
                $middleware_for_method,
                $controller_middleware->toArray()
            );
        }

        return $middleware_for_method;
    }

    /**
     * @interal
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    final protected function redirect(): Redirector
    {
        return $this->container->get(Redirector::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    final protected function url(): UrlGeneratorInterface
    {
        return $this->container->get(UrlGeneratorInterface::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function render(string $template_identifier, array $data = []): Response
    {
        /** @var TemplateRenderer $renderer */
        $renderer = $this->container->get(TemplateRenderer::class);
        Assert::isInstanceOf($renderer, TemplateRenderer::class);
        return $this->respond()->html($renderer->render($template_identifier, $data));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     *
     * @psalm-suppress MixedInferredReturnType
     * @psalm-suppress MixedReturnStatement
     */
    final protected function respond(): ResponseFactory
    {
        return $this->container->get(ResponseFactory::class);
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

}