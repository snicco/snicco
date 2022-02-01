<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Snicco\Component\HttpRouting\Routing\AdminDashboard\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\InternalUrlGenerator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

trait CreateUrlGenerator
{

    final protected function createUrlGenerator(
        UrlGenerationContext $context = null,
        Routes $routes = null
    ): UrlGenerator {
        return new InternalUrlGenerator(
            $routes ?? new RouteCollection([]),
            $context ?? UrlGenerationContext::forConsole('127.0.0.1'),
            WPAdminArea::fromDefaults(),
        );
    }

}