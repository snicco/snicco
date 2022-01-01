<?php

declare(strict_types=1);

/** @var \Snicco\Core\Routing\Internal\Router $router */

use Snicco\Core\Routing\Internal\Router;
use Snicco\Core\ExceptionHandling\Exceptions\HttpException;
use Snicco\SessionBundle\Exceptions\InvalidCsrfTokenException;

$router->get('error/500', function () {
    throw new HttpException(500, 'Secret logging stuff.');
});

$router->get('error/400', function () {
    throw new HttpException(400, 'Secret logging stuff.');
});

$router->get('error/419', function () {
    throw new InvalidCsrfTokenException('Secret logging stuff.');
});

$router->get('error/fatal', function () {
    trigger_error('Secret logging stuff.', E_USER_ERROR);
});


