<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;
    use WPEmerge\Session\Controllers\LogoutController;
    use WPEmerge\Session\Controllers\WpLoginRedirectController;

    /** @var Router $router */

    $router->match(['GET', 'POST'], '/wp-login.php', WpLoginRedirectController::class);

    $router->get('/auth/logout/{user_id}', LogoutController::class)
           ->middleware('signed')
           ->name('auth.logout')
           ->andAlphaNumerical('user_id');
