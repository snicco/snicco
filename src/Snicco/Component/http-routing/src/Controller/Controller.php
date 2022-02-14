<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Controller;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Renderer\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\StrArr\Arr;
use Webmozart\Assert\Assert;

use function array_filter;
use function array_merge;

abstract class Controller
{

    /**
     * @var ControllerMiddleware[]
     */
    private array $middleware = [];

    private ContainerInterface $container;

    /**
     * @psalm-mutation-free
     *
     * @interal
     *
     * @return class-string<MiddlewareInterface>[]
     */
    final public function getMiddleware(string $controller_method): array
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
    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function redirect(): Redirector
    {
        /** @var Redirector $r */
        $r = $this->container->get(Redirector::class);
        return $r;
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function url(): UrlGenerator
    {
        /** @var UrlGenerator $url */
        $url = $this->container->get(UrlGenerator::class);
        return $url;
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
     */
    final protected function respond(): ResponseFactory
    {
        /** @var ResponseFactory $response */
        $response = $this->container->get(ResponseFactory::class);
        return $response;
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