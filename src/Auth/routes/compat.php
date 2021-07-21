<?php


    declare(strict_types = 1);

    use Snicco\Auth\Controllers\BulkPasswordResetEmailController;
    use Snicco\Auth\Controllers\PasswordResetEmailController;
    use Snicco\Auth\Controllers\WpLoginRedirectController;
    use Snicco\Routing\Router;

    /** @var Router $router */

    $router->get('/wp-admin/users.php', [BulkPasswordResetEmailController::class, 'store'])
           ->where('query_string', ['action' => 'resetpassword']);

    $router->post('/wp-admin/admin-ajax.php/send-password-reset', [PasswordResetEmailController::class, 'store'])
           ->middleware(['auth']);

    $router->any('/wp-login.php', [WpLoginRedirectController::class]);
