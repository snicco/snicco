<?php

declare(strict_types=1);

use Snicco\Bundle\Testing\Tests\fixtures\WebTestCaseController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router) {
    $router->get('foo', '/foo', WebTestCaseController::class);
    $router->get('query-params-as-json', '/query-params-as-json', [WebTestCaseController::class, 'queryParams']);
    $router->get('cookies-as-json', '/cookies-as-json', [WebTestCaseController::class, 'cookiesAsJson']);
    $router->get('check-api-frontend', '/check-api', [WebTestCaseController::class, 'checkIfApi']);
    $router->get('full', '/full-url', [WebTestCaseController::class, 'fullUrl']);
    $router->get('custom-server', '/custom-server-vars', [WebTestCaseController::class, 'serverVars']);
    $router->post('body-as-json', '/body-as-json', [WebTestCaseController::class, 'bodyAsJson']);
    $router->post('files-as-json', '/files-as-json', [WebTestCaseController::class, 'filesAsJson']);
};