<?php


    declare(strict_types = 1);

    /** @var Router $router */

    use WPMvc\Controllers\RedirectController;
    use WPMvc\Routing\Router;

    $router->get('/')->noAction()->name('home');

    $router->get('/wp-admin/index.php')->noAction()->name('dashboard');

    $router->get('redirect/exit', [RedirectController::class, 'exit'])
           ->middleware( 'robots')
           ->name('redirect.protection');