<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Tests\fixtures\Controller\HttpRunnerTestController;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Middleware\MiddlewareOne;
use Snicco\Bundle\HttpRouting\Tests\fixtures\Middleware\MiddlewareTwo;
use Snicco\Bundle\HttpRouting\Tests\fixtures\RoutingBundleTestController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router): void {
    $router->get('frontend1', '/frontend', RoutingBundleTestController::class);

    $router->get('delegate', '/delegate');

    $router->view('/view', dirname(__DIR__) . '/templates/greeting.php', [
        'greet' => 'Calvin',
    ]);

    $router->redirect('/foo', '/bar');

    $router->get('trigger-notice', '/trigger-notice', [RoutingBundleTestController::class, 'triggerNotice']);

    $router->get(
        'trigger-deprecation',
        '/trigger-deprecation',
        [RoutingBundleTestController::class, 'triggerDeprecation']
    );

    $router->get('middleware1', '/middleware1', RoutingBundleTestController::class)
        ->middleware(MiddlewareOne::class);

    $router->get('middleware2', '/middleware2', RoutingBundleTestController::class)
        ->middleware([MiddlewareOne::class, MiddlewareTwo::class]);

    $router->get('no-response', '/no-response', [HttpRunnerTestController::class, 'noResponse']);
    $router->get('stream', '/stream', [HttpRunnerTestController::class, 'stream']);
};
