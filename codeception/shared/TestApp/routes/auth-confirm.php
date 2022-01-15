<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Component\HttpRouting\Routing\Router;

TestApp::route()->prefix('auth-confirm')->name('auth.confirm.test')->createInGroup(
    function (Router $router) {
        $router->get('foo', function () {
            return 'Access to foo granted';
        })->middleware('auth.confirmed');
    }
);