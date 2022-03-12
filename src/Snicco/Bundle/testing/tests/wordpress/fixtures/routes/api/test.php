<?php

declare(strict_types=1);

use Snicco\Bundle\Testing\Tests\wordpress\fixtures\WebTestCaseController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\WebRoutingConfigurator;

return function (WebRoutingConfigurator $router): void {
    $router->get('check-if-api', '/check-api', [WebTestCaseController::class, 'checkIfApi']);
};
