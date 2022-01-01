<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Response;
use Snicco\Core\Application\Config;
use Snicco\Core\Contracts\Redirector;
use Snicco\Core\Shared\ContainerAdapter;
use Snicco\Core\Contracts\ResponseFactory;
use Snicco\Core\Contracts\TemplateRenderer;
use Snicco\Core\Contracts\UrlGeneratorInterface;

abstract class AbstractController
{
    
    /**
     * @var ControllerMiddleware[]
     */
    private $middleware = [];
    
    /**
     * @var ContainerAdapter
     */
    private $container;
    
    /**
     * @interal
     */
    public function getMiddleware(string $method = null) :array
    {
        $middleware = array_filter(
            $this->middleware,
            function (ControllerMiddleware $controller_middleware) use ($method) {
                return $controller_middleware->appliesTo($method);
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
    public function setContainer(ContainerAdapter $container)
    {
        $this->container = $container;
    }
    
    protected function middleware(string $middleware_name) :ControllerMiddleware
    {
        return $this->middleware[] = new ControllerMiddleware($middleware_name);
    }
    
    protected function redirect() :Redirector
    {
        return $this->container[Redirector::class];
    }
    
    protected function url() :UrlGeneratorInterface
    {
        return $this->container[UrlGeneratorInterface::class];
    }
    
    protected function respond() :ResponseFactory
    {
        return $this->container[ResponseFactory::class];
    }
    
    /**
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        /** @var Config $config */
        $config = $this->container[ContainerAdapter::class];
        Assert::isInstanceOf(Config::class, $config);
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