<?php

declare(strict_types=1);

use Snicco\Routing\Router;
use Tests\Codeception\shared\TestApp\TestApp;

$router = TestApp::resolve(Router::class);

$router->get('foo', function () {
    return 'foo-other-route';
});

$router->get('web-other', function () {
    return 'web-other';
});