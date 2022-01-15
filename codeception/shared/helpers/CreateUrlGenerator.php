<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\HttpRouting\Routing\Route\Routes;
use Snicco\HttpRouting\Routing\Route\RouteCollection;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

trait CreateUrlGenerator
{
    
    final protected function createUrlGenerator(UrlGenerationContext $context = null, Routes $routes = null) :UrlGenerator
    {
        return new InternalUrlGenerator(
            $routes ?? new RouteCollection([]),
            $context ?? UrlGenerationContext::forConsole('localhost.com'),
            WPAdminArea::fromDefaults(),
        );
    }
    
}