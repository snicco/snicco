<?php

declare(strict_types=1);

use Snicco\Routing\Router;
use Snicco\Auth\Controllers\WPLoginRedirectController;
use Snicco\Auth\Controllers\Compat\PasswordResetEmailController;
use Snicco\Auth\Controllers\Compat\BulkPasswordResetEmailController;

/** @var Router $router */

$router->get('/wp-admin/users.php', [BulkPasswordResetEmailController::class, 'store'])
       ->where('query_string', ['action' => 'resetpassword']);

$router->post(
    '/wp-admin/admin-ajax.php/send-password-reset',
    [PasswordResetEmailController::class, 'store']
)->middleware(['auth']);

$router->any('/wp-login.php', [WPLoginRedirectController::class]);
