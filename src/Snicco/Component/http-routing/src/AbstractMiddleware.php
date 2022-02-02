<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\Response;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\TemplateRenderer;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Webmozart\Assert\Assert;

abstract class AbstractMiddleware implements MiddlewareInterface
{

    private ContainerInterface $container;

    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request instanceof Request) {
            $request = new Request($request);
        }

        if (!$handler instanceof NextMiddleware) {
            $handler = new NextMiddleware(function (Request $request) use ($handler) {
                return $handler->handle($request);
            });
        }

        return $this->handle($request, $handler);
    }

    abstract public function handle(Request $request, NextMiddleware $next): ResponseInterface;

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function redirect(): Redirector
    {
        return $this->container->get(Redirector::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function url(): UrlGenerator
    {
        return $this->container->get(UrlGenerator::class);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function render(string $template_identifier, array $data = []): Response
    {
        /** @var TemplateRenderer $renderer */
        $renderer = $this->container->get(TemplateRenderer::class);
        Assert::isInstanceOf(TemplateRenderer::class, $renderer);
        return $this->respond()->html($renderer->render($template_identifier, $data));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function respond(): ResponseFactory
    {
        return $this->container->get(ResponseFactory::class);
    }

}