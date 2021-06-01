<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;

    /**
     * @var Router $router
     */
    $router->get('other', function () {

        return 'other';

    });