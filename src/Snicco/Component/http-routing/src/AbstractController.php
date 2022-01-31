<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Webmozart\Assert\Assert;

/**
 * @api
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
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }

    final protected function redirect(): Redirector
    {
        return $this->container->get(Redirector::class);
    }

    final protected function url(): UrlGenerator
    {
        return $this->container->get(UrlGenerator::class);
    }

    final protected function render(string $template_identifier, array $data = []): Response
    {
        /** @var TemplateRenderer $renderer */
        $renderer = $this->container->get(TemplateRenderer::class);
        Assert::isInstanceOf(TemplateRenderer::class, $renderer);
        return $this->respond()->html($renderer->render($template_identifier, $data));
    }

    final protected function respond(): ResponseFactory
    {
        return $this->container->get(ResponseFactory::class);
    }

    final protected function middleware(string $middleware_name): ControllerMiddleware
    {
        return $this->middleware[] = new ControllerMiddleware($middleware_name);
    }

}