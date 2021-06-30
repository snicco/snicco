<?php


    declare(strict_types = 1);

    use WPEmerge\Auth\Controllers\BulkPasswordResetEmailController;
    use WPEmerge\Auth\Controllers\PasswordResetEmailController;
    use WPEmerge\Auth\Controllers\WpLoginRedirectController;
    use WPEmerge\Routing\Router;

    /** @var Router $router */

    $router->get('/wp-admin/users.php', [BulkPasswordResetEmailController::class, 'store'])
           ->where('query_string', ['action' => 'resetpassword']);

    $router->post('/wp-admin/admin-ajax.php/send-password-reset', [PasswordResetEmailController::class, 'store'])
           ->middleware(['auth']);

    $router->any('/wp-login.php', [WpLoginRedirectController::class]);
