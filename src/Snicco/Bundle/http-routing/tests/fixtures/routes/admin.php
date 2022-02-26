<?php

declare(strict_types=1);

use Snicco\Bundle\HttpRouting\Tests\fixtures\Controller\HttpRunnerTestController;
use Snicco\Component\HttpRouting\Routing\Admin\AdminMenuItem;
use Snicco\Component\HttpRouting\Routing\RoutingConfigurator\AdminRoutingConfigurator;

return function (AdminRoutingConfigurator $router) {
    $router->page('foo', 'admin.php/foo', HttpRunnerTestController::class, [
        AdminMenuItem::MENU_TITLE => 'FOO_TITLE',
        AdminMenuItem::CAPABILITY => 'read',

    ]);

    $router->page('admin_redirect', 'admin.php/admin_redirect', [HttpRunnerTestController::class, 'adminRedirect'], [
        AdminMenuItem::POSITION => -10
    ]);

    $router->page('client_error', 'admin.php/client_error', [HttpRunnerTestController::class, 'clientError']);
    $page = $router->page('server_error', 'admin.php/server_error', [HttpRunnerTestController::class, 'serverError']);
    $router->page('do_nothing', 'admin.php/do_nothing', [HttpRunnerTestController::class, 'noResponse'], [], $page);
};