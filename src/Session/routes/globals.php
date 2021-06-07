<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;
    use WPEmerge\Session\Controllers\LogoutController;
    use WPEmerge\Session\Controllers\LogoutRedirectController;

    /** @var Router $router */

    $router->middleware('csrf')->get('/auth/logout', LogoutController::class)->name('auth.logout');

    $router->match(['GET', 'POST'],'/wp-login.php', LogoutRedirectController::class);