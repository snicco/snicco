<?php

declare(strict_types=1);

/** @var \Snicco\Core\Routing\Internal\Router $router */

use Snicco\Core\Routing\Internal\Router;
use Snicco\Core\Controllers\RedirectController;

$router->get('/wp-admin/index.php')->noAction()->name('dashboard');

$router->get('redirect/exit', [RedirectController::class, 'exit'])
       ->middleware('robots')
       ->name('redirect.protection');