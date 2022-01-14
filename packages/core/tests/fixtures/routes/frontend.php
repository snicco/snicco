<?php

declare(strict_types=1);

use Tests\Core\unit\Routing\RouteLoaderTest;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;
use Snicco\Core\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'web1',
        RouteLoaderTest::WEB_PATH,
        RoutingTestController::class
    );
    
    if (true === RouteLoaderTest::$web_include_partial) {
        $router->include(__DIR__.'/_partial.php');
    }
    
    $router->fallback([RoutingTestController::class, 'fallback']);
};
