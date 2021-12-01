<?php

declare(strict_types=1);

use Snicco\Routing\Router;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\Core\fixtures\Middleware\GlobalMiddleware;

TestApp::route()->group(function (Router $router) {
    $router->get('foo', function () {
        return 'foo';
    })->middleware('custom_group');
    
    $router->get('route-with-global', function () {
        return 'route-with-global';
    })->middleware(GlobalMiddleware::class);
}, ['prefix' => 'middleware']);