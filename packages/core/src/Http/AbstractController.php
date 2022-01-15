<?php

declare(strict_types=1);

namespace Snicco\Core\Http;

use Webmozart\Assert\Assert;
use Snicco\Core\Http\Psr7\Response;
use Psr\Container\ContainerInterface;
use Snicco\Core\Configuration\ReadOnlyConfig;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;

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
    public function setContainer(ContainerInterface $container)
    {
        $this->container = $container;
    }
    
    final protected function redirect() :Redirector
    {
        return $this->container[Redirector::class];
    }
    
    final protected function url() :UrlGenerator
    {
        return $this->container[UrlGenerator::class];
    }
    
    final protected function respond() :ResponseFactory
    {
        return $this->container[ResponseFactory::class];
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
    
    final protected function middleware(string $middleware_name) :ControllerMiddleware
    {
        return $this->middleware[] = new ControllerMiddleware($middleware_name);
    }
    
}