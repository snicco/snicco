<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGeneratorInterface;
use Webmozart\Assert\Assert;

/**
 * @api
 *
 *
 */
abstract class AbstractController
{

    /**
     * @var ControllerMiddleware[]
     */
    private array $middleware = [];

    private ContainerInterface $container;

    /**
     * @interal
     * @return string[]
     */
    public function getMiddleware(string $controller_method = null): array
    {
        $middleware = array_filter(
            $this->middleware,
            function (ControllerMiddleware $controller_middleware) use ($controller_method) {
                return $controller_middleware->appliesTo($controller_method);
            }
        );

        return array_values(
            array_map(function (ControllerMiddleware $middleware) {
                return $middleware->name();
            }, $middleware)
        );
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

    final protected function middleware(string $middleware_name): ControllerMiddleware
    {
        return $this->middleware[] = new ControllerMiddleware($middleware_name);
    }

}