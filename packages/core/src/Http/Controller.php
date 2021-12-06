<?php

declare(strict_types=1);

namespace Snicco\Http;

use Snicco\Routing\UrlGenerator;
use Snicco\Contracts\ResponseFactory;

class Controller
{
    
    /**
     * @var ResponseFactory
     */
    protected $response_factory;
    
    /**
     * @var UrlGenerator
     */
    protected $url;
    
    /**
     * @var ControllerMiddleware[]
     */
    private $middleware = [];
    
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
    
    public function giveResponseFactory(ResponseFactory $response_factory)
    {
        $this->response_factory = $response_factory;
    }
    
    public function giveUrlGenerator(UrlGenerator $url)
    {
        $this->url = $url;
    }
    
    protected function middleware(string $middleware_name) :ControllerMiddleware
    {
        return $this->middleware[] = new ControllerMiddleware($middleware_name);
    }
    
}