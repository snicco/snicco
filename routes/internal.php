<?php


    declare(strict_types = 1);

    /** @var Router $router */

    use WPEmerge\Controllers\RedirectController;
    use WPEmerge\Routing\Router;

    $router->get('/')->noAction()->name('home');

    $router->get('/wp-admin')->noAction()->name('dashboard');

    $router->get('redirect/exit', [RedirectController::class, 'exit'])
           ->middleware( 'robots')
           ->name('redirect.protection');