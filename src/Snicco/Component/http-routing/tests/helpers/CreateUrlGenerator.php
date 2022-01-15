<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;
use Snicco\Component\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;

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