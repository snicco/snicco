<?php

declare(strict_types=1);

use Snicco\Bundle\Session\Middleware\StatefulRequest;
use Snicco\Bundle\Testing\Tests\wordpress\fixtures\MiddlewareThatAlwaysThrowsException;
use Snicco\Bundle\Testing\Tests\wordpress\fixtures\WebTestCaseController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router): void {
    $router->get('foo', '/foo', WebTestCaseController::class);
    $router->get('query-params-as-json', '/query-params-as-json', [WebTestCaseController::class, 'queryParams']);
    $router->get('cookies-as-json', '/cookies-as-json', [WebTestCaseController::class, 'cookiesAsJson']);
    $router->get('headers-as-json', '/headers-as-json', [WebTestCaseController::class, 'headersAsJson']);
    $router->get('check-api-frontend', '/check-api', [WebTestCaseController::class, 'checkIfApi']);
    $router->get('full', '/full-url', [WebTestCaseController::class, 'fullUrl']);
    $router->get('custom-server', '/custom-server-vars', [WebTestCaseController::class, 'serverVars']);
    $router->post(
        'session-counter',
        '/increment-counter',
        [WebTestCaseController::class, 'incrementCounter']
    )->middleware(StatefulRequest::class);
    $router->post('body-as-json', '/body-as-json', [WebTestCaseController::class, 'bodyAsJson']);
    $router->post('files-as-json', '/files-as-json', [WebTestCaseController::class, 'filesAsJson']);
    $router->post('send-mail', '/send-mail', [WebTestCaseController::class, 'sendMail']);

    $router->post('raw-body', '/raw-body', [WebTestCaseController::class, 'rawBody']);

    $router->get(
        'force-exception-middleware',
        '/force-exception-middleware',
        WebTestCaseController::class
    )->middleware(
        MiddlewareThatAlwaysThrowsException::class
    );
};
