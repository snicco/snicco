<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Middleware;

use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\Component\HttpRouting\Http\Psr7\ResponseFactory;
use Snicco\Component\HttpRouting\Http\Redirector;
use Snicco\Component\HttpRouting\Http\Response\ViewResponse;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

abstract class Middleware implements MiddlewareInterface
{

    private ContainerInterface $container;

    final public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    abstract public function handle(Request $request, NextMiddleware $next): ResponseInterface;

    final public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = Request::fromPsr($request);

        if (!$handler instanceof NextMiddleware) {
            $handler = new NextMiddleware(function (Request $request) use ($handler) {
                return $handler->handle($request);
            });
        }

        return $this->handle($request, $handler);
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
     * @param array<string,mixed> $data
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    final protected function render(string $template_identifier, array $data = []): ViewResponse
    {
        return $this->respond()->view($template_identifier, $data);
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

}