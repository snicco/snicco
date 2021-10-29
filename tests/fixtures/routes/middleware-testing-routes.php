<?php

declare(strict_types=1);

use Tests\stubs\TestApp;
use Snicco\Routing\Router;
use Tests\fixtures\Middleware\GlobalMiddleware;

TestApp::route()->group(function (Router $router) {
    
    $router->get('foo', function () {
        return 'foo';
    })->middleware('custom_group');
    
    $router->get('route-with-global', function () {
        return 'route-with-global';
    })->middleware(GlobalMiddleware::class);
    
}, ['prefix' => 'middleware'],);