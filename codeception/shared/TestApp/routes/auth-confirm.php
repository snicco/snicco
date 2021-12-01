<?php

declare(strict_types=1);

use Snicco\Routing\Router;
use Tests\Codeception\shared\TestApp\TestApp;

TestApp::route()->prefix('auth-confirm')->name('auth.confirm.test')->group(
    function (Router $router) {
        $router->get('foo', function () {
            return 'Access to foo granted';
        })->middleware('auth.confirmed');
    }
);