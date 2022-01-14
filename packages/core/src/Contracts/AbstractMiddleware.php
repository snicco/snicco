<?php

declare(strict_types=1);

namespace Snicco\Core\Contracts;

use Webmozart\Assert\Assert;
use Snicco\Core\DIContainer;
use Snicco\Core\Http\Psr7\Request;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Middleware\Delegate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Snicco\Core\Configuration\WritableConfig;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;

abstract class AbstractMiddleware implements MiddlewareInterface
{
    
    private DIContainer $container;
    
    public function setContainer(DIContainer $container)
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
    
    protected function respond() :ResponseFactory
    {
        return $this->container[ResponseFactory::class];
    }
    
    protected function redirect() :Redirector
    {
        return $this->container[Redirector::class];
    }
    
    protected function url() :UrlGenerator
    {
        return $this->container[UrlGenerator::class];
    }
    
    /**
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        /** @var WritableConfig $config */
        $config = $this->container[WritableConfig::class];
        Assert::isInstanceOf(WritableConfig::class, $config);
        return $config->get($key, $default);
    }
    
    protected function render(string $template_identifier, array $data = []) :Response
    {
        /** @var TemplateRenderer $renderer */
        $renderer = $this->container[TemplateRenderer::class];
        Assert::isInstanceOf(TemplateRenderer::class, $renderer);
        return $this->respond()->html($renderer->render($template_identifier, $data));
    }
    
}