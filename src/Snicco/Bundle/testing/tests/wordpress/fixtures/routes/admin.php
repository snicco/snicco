<?php

declare(strict_types=1);

use Snicco\Bundle\Testing\Tests\wordpress\fixtures\WebTestCaseController;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

return function (AdminRoutingConfigurator $router) {
    $router->page('foo', 'admin.php/foo', [WebTestCaseController::class, 'admin']);
};
