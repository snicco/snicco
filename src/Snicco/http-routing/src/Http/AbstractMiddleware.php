<?php

declare(strict_types=1);

namespace Snicco\HttpRouting\Http;

use Webmozart\Assert\Assert;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Snicco\HttpRouting\Http\Psr7\Request;
use Snicco\HttpRouting\Http\Psr7\Response;
use Snicco\HttpRouting\Middleware\Delegate;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Core\Configuration\ReadOnlyConfig;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGenerator;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    
    private ContainerInterface $container;
    
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    /**
     * @param  Request  $request
     * @param  RequestHandlerInterface  $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) :ResponseInterface
    {
        return $this->handle($request, $handler);
    }
    
    /**
     * @param  Request  $request
     * @param  Delegate  $next  This class can be called as a closure. $next($request)
     *
     * @return ResponseInterface
     */
    abstract public function handle(Request $request, Delegate $next) :ResponseInterface;
    
    final protected function respond() :ResponseFactory
    {
        return $this->container[ResponseFactory::class];
    }
    
    final protected function redirect() :Redirector
    {
        return $this->container[Redirector::class];
    }
    
    final protected function url() :UrlGenerator
    {
        return $this->container[UrlGenerator::class];
    }
    
    final protected function config() :ReadOnlyConfig
    {
        /** @var ReadOnlyConfig $config */
        $config = $this->container[ReadOnlyConfig::class];
        Assert::isInstanceOf(ReadOnlyConfig::class, $config);
        return $config;
    }
    
    final protected function render(string $template_identifier, array $data = []) :Response
    {
        /** @var TemplateRenderer $renderer */
        $renderer = $this->container[TemplateRenderer::class];
        Assert::isInstanceOf(TemplateRenderer::class, $renderer);
        return $this->respond()->html($renderer->render($template_identifier, $data));
    }
    
}