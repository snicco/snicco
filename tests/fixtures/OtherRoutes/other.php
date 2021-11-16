<?php

declare(strict_types=1);

use Snicco\Routing\Router;

/**
 * @var Router $router
 */
$router->get('other', function () {
    return 'other';
});