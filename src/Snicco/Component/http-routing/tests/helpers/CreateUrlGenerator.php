<?php

declare(strict_types=1);

namespace Snicco\Component\HttpRouting\Tests\helpers;

use Snicco\Component\HttpRouting\Routing\Admin\WPAdminArea;
use Snicco\Component\HttpRouting\Routing\Route\RouteCollection;
use Snicco\Component\HttpRouting\Routing\Route\Routes;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\Generator;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerationContext;
use Snicco\Component\HttpRouting\Routing\UrlGenerator\UrlGenerator;

/**
 * @interal
 */
trait CreateUrlGenerator
{

    final protected function createUrlGenerator(
        UrlGenerationContext $context = null,
        Routes $routes = null
    ): UrlGenerator {
        return new Generator(
            $routes ?? new RouteCollection([]),
            $context ?? UrlGenerationContext::forConsole('127.0.0.1'),
            WPAdminArea::fromDefaults(),
        );
    }

}