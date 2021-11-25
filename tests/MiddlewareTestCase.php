<?php

declare(strict_types=1);

namespace Tests;

use Snicco\View\ViewEngine;
use Tests\stubs\TestViewFactory;
use Snicco\Routing\RouteCollection;
use Snicco\Contracts\RouteUrlGenerator;
use Tests\concerns\CreatePsr17Factories;
use Snicco\View\Contracts\ViewFactoryInterface;
use Snicco\Routing\FastRoute\FastRouteUrlGenerator;
use Snicco\Testing\MiddlewareTestCase as FrameworkMiddlewareTestCase;

class MiddlewareTestCase extends FrameworkMiddlewareTestCase
{
    
    use CreatePsr17Factories;
    
    protected RouteCollection $routes;
    
    protected function routeUrlGenerator() :RouteUrlGenerator
    {
        return new FastRouteUrlGenerator($this->routes = new RouteCollection());
    }
    
    protected function viewEngine() :ViewEngine
    {
        return new ViewEngine(new TestViewFactory());
    }
    
}