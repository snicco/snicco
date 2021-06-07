<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;
    use WPEmerge\Session\Controllers\LogoutController;
    use WPEmerge\Session\Controllers\LogoutRedirectController;

    /** @var Router $router */

    $router->get('/auth/logout/{user_id}', LogoutController::class)
           ->middleware('validSignature')
           ->name('auth.logout')
           ->andAlphaNumerical('user_id');

    $router->match(['GET', 'POST'], '/wp-login.php', LogoutRedirectController::class);