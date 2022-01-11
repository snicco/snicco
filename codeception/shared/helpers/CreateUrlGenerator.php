<?php

declare(strict_types=1);

namespace Tests\Codeception\shared\helpers;

use Snicco\Core\Routing\Routes;
use Snicco\Core\Routing\UrlGenerator;
use Snicco\Core\Routing\Internal\Generator;
use Snicco\Core\Routing\Internal\RouteCollection;
use Snicco\Core\Routing\Internal\WPAdminDashboard;
use Snicco\Core\Routing\Internal\UrlGenerationContext;

trait CreateUrlGenerator
{
    
    final protected function createUrlGenerator(UrlGenerationContext $context = null, Routes $routes = null) :UrlGenerator
    {
        return new Generator(
            $routes ?? new RouteCollection(),
            $context ?? UrlGenerationContext::forConsole('localhost.com'),
            WPAdminDashboard::fromDefaults(),
        );
    }
    
}