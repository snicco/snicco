<?php

declare(strict_types=1);

use Tests\Codeception\shared\TestApp\TestApp;
use Snicco\Component\HttpRouting\Http\Psr7\Request;
use Snicco\SessionBundle\Exceptions\InvalidCsrfTokenException;
use Snicco\Component\Core\ExceptionHandling\Exceptions\AuthorizationException;

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
    throw new \Snicco\Component\Core\ExceptionHandling\Exceptions\HttpException(500, 'sensitive info');
});

