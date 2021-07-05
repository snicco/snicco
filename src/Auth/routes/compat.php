<?php


    declare(strict_types = 1);

    use BetterWP\Auth\Controllers\BulkPasswordResetEmailController;
    use BetterWP\Auth\Controllers\PasswordResetEmailController;
    use BetterWP\Auth\Controllers\WpLoginRedirectController;
    use BetterWP\Routing\Router;

    /** @var Router $router */

    $router->get('/wp-admin/users.php', [BulkPasswordResetEmailController::class, 'store'])
           ->where('query_string', ['action' => 'resetpassword']);

    $router->post('/wp-admin/admin-ajax.php/send-password-reset', [PasswordResetEmailController::class, 'store'])
           ->middleware(['auth']);

    $router->any('/wp-login.php', [WpLoginRedirectController::class]);
