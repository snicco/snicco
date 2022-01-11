<?php

declare(strict_types=1);

use Snicco\Core\Routing\WebRoutingConfigurator;
use Tests\Core\unit\Routing\PHPFileRouteLoaderTest;
use Tests\Core\fixtures\Controllers\Web\RoutingTestController;

return function (WebRoutingConfigurator $router) {
    $router->get(
        'web1',
        PHPFileRouteLoaderTest::WEB_PATH,
        RoutingTestController::class
    );
    
    if (true === PHPFileRouteLoaderTest::$web_include_partial) {
        $router->include(__DIR__.'/_partial.php');
    }
    
    $router->fallback([RoutingTestController::class, 'fallback']);
};
