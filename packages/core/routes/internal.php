<?php

declare(strict_types=1);

/** @var Router $router */

use Snicco\Core\Routing\Router;
use Snicco\Core\Controllers\RedirectAbstractController;

$router->get('/')->noAction()->name('home');

$router->get('/wp-admin/index.php')->noAction()->name('dashboard');

$router->get('redirect/exit', [RedirectAbstractController::class, 'exit'])
       ->middleware('robots')
       ->name('redirect.protection');