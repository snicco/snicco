<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Routing\Route\Routes;
use Snicco\Core\Routing\Route\RouteCollection;
use Snicco\Core\Routing\UrlGenerator\UrlGenerator;
use Snicco\Core\Routing\AdminDashboard\WPAdminArea;
use Snicco\Core\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Core\Routing\UrlGenerator\InternalUrlGenerator;

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