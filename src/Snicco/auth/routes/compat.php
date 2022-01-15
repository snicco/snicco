<?php

declare(strict_types=1);

use Snicco\Component\HttpRouting\Routing\Router;
use Snicco\Auth\Controllers\WPLoginRedirectAbstractController;
use Snicco\Auth\Controllers\Compat\PasswordResetEmailAbstractController;
use Snicco\Auth\Controllers\Compat\BulkPasswordResetEmailAbstractController;

/** @var Router $router */

$router->get('/wp-admin/users.php', [BulkPasswordResetEmailAbstractController::class, 'store']);

$router->post(
    '/wp-admin/admin-ajax.php/send-password-reset',
    [PasswordResetEmailAbstractController::class, 'store']
)->middleware(['auth']);

$router->any('/wp-login.php', [WPLoginRedirectAbstractController::class]);
