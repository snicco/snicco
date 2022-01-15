<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Component\HttpRouting\Routing\Router;

$router = TestApp::resolve(Router::class);

$router->get('foo', function () {
    return 'foo-other-route';
});

$router->get('web-other', function () {
    return 'web-other';
});