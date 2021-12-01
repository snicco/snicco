<?php

declare(strict_types=1);

use Snicco\Http\Psr7\Request;
use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Session\Exceptions\InvalidCsrfTokenException;
use Snicco\ExceptionHandling\Exceptions\AuthorizationException;

$router = TestApp::route();
$router->get('admin.php/bar', function (Request $request) {
    return strtoupper($request->input('page')).'_ADMIN';
});

$router->get('admin.php/foo', function (Request $request) {
    return strtoupper($request->input('page')).'_ADMIN';
})->name('foo');

$router->post('biz', function (Request $request) {
    return strtoupper($request->input('page')).'_ADMIN';
});

$router->get('admin.php/error', function () {
    throw new InvalidCsrfTokenException();
});
$router->redirect('profile.php', '/foo');

$router->redirect('admin.php/redirect', '/foobar');

$router->get('admin.php/client-error', function () {
    throw new AuthorizationException();
});

$router->get('admin.php/server-error', function () {
    throw new \Snicco\ExceptionHandling\Exceptions\HttpException(500, 'sensitive info');
});

