<?php


    declare(strict_types = 1);

    use WPEmerge\Auth\Controllers\BulkPasswordResetEmailController;
    use WPEmerge\Routing\Router;

    /** @var Router $router */

    $router->get('/wp-admin/users.php', [BulkPasswordResetEmailController::class, 'store'])
           ->where('query_string', ['action' => 'resetpassword']);

