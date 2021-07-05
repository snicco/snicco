<?php


    declare(strict_types = 1);

    /** @var Router $router */

    use BetterWP\Controllers\RedirectController;
    use BetterWP\Routing\Router;

    $router->get('/')->noAction()->name('home');

    $router->get('/wp-admin/index.php')->noAction()->name('dashboard');

    $router->get('redirect/exit', [RedirectController::class, 'exit'])
           ->middleware( 'robots')
           ->name('redirect.protection');