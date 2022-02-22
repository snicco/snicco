<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Tests\unit\fixtures\RoutingBundleTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get('frontend1', '/frontend', RoutingBundleTestController::class);
    $router->get('delegate', '/delegate');
    $router->view('/view', dirname(__DIR__) . '/templates/greeting.php', ['greet' => 'Calvin']);
    $router->redirect('/foo', '/bar');
};
