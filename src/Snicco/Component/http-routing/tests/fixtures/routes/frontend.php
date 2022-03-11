<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;
use Snicco\Component\HttpRouting\Tests\fixtures\Controller\RoutingTestController;
use Snicco\Component\HttpRouting\Tests\Routing\RouteLoader\PHPFileRouteLoaderTest;

return function (WebRoutingConfigurator $router): void {
    $router->get('web1', PHPFileRouteLoaderTest::WEB_PATH, RoutingTestController::class);

    if (PHPFileRouteLoaderTest::$web_include_partial) {
        $router->include(__DIR__ . '/_partial.php');
    }

    $router->fallback([RoutingTestController::class, 'fallback']);
};
