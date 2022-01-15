<?php

declare(strict_types=1);

use Snicco\HttpRouting\Routing\Router;
use Tests\Codeception\shared\TestApp\TestApp;
use Tests\HttpRouting\fixtures\GlobalMiddleware;

TestApp::route()->createInGroup(function (Router $router) {
    $router->get('foo', function () {
        return 'foo';
    })->middleware('custom_group');
    
    $router->get('route-with-global', function () {
        return 'route-with-global';
    })->middleware(GlobalMiddleware::class);
}, ['prefix' => 'middleware']);