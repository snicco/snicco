<?php

declare(strict_types=1);

use Snicco\Core\Routing\Internal\Router;
use Tests\Codeception\shared\TestApp\TestApp;

TestApp::route()->prefix('auth-confirm')->name('auth.confirm.test')->createInGroup(
    function (Router $router) {
        $router->get('foo', function () {
            return 'Access to foo granted';
        })->middleware('auth.confirmed');
    }
);