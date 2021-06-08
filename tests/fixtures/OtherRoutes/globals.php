<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;

    /**
     * @var Router $router
     */

    $router->prefix('other-globals')->group(function (Router $router) {

        $router->get('foo', function () {
            return 'OTHER_GLOBALS_FOO';
        });

    });
