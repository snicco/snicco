<?php

declare(strict_types=1);

/** @var \Snicco\Core\Routing\Router $router */

use Snicco\Core\Controllers\RedirectController;

$router->get('/wp-admin/index.php')->noAction()->name('dashboard');

$router->get('redirect/exit', [RedirectController::class, 'exit'])
       ->middleware('robots')
       ->name('redirect.protection');