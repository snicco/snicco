<?php

declare(strict_types=1);

use Snicco\HttpRouting\Routing\Router;

/**
 * @var Router $router
 */
$router->get('other', function () {
    return 'other';
});