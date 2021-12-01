<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;

$router = TestApp::route();

$router->post('foo_action')->handle(function () {
    return 'FOO_AJAX_ACTION';
})->name('foo');