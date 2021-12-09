<?php

declare(strict_types=1);

namespace Tests\Core;

use Snicco\Core\Routing\RouteCollection;
use Snicco\Core\Contracts\RouteUrlGenerator;
use Snicco\Core\Routing\FastRoute\FastRouteUrlGenerator;
use Tests\Codeception\shared\helpers\CreatePsr17Factories;
use Snicco\Testing\MiddlewareTestCase as FrameworkMiddlewareTestCase;

class MiddlewareTestCase extends FrameworkMiddlewareTestCase
{
    
    use CreatePsr17Factories;
    
    protected RouteCollection $routes;
    
    protected function routeUrlGenerator() :RouteUrlGenerator
    {
        return new FastRouteUrlGenerator($this->routes = new RouteCollection());
    }
    
}