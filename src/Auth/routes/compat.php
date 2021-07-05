<?php


    declare(strict_types = 1);

    use WPMvc\Auth\Controllers\BulkPasswordResetEmailController;
    use WPMvc\Auth\Controllers\PasswordResetEmailController;
    use WPMvc\Auth\Controllers\WpLoginRedirectController;
    use WPMvc\Routing\Router;

    /** @var Router $router */

    $router->get('/wp-admin/users.php', [BulkPasswordResetEmailController::class, 'store'])
           ->where('query_string', ['action' => 'resetpassword']);

    $router->post('/wp-admin/admin-ajax.php/send-password-reset', [PasswordResetEmailController::class, 'store'])
           ->middleware(['auth']);

    $router->any('/wp-login.php', [WpLoginRedirectController::class]);
