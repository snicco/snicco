<?php


    declare(strict_types = 1);

    use WPEmerge\Routing\Router;

    require __DIR__ . DS . 'redirects.php';

    /**
     * @var Router $router
     */

    $router->prefix('globals')->group(function (Router $router) {

       $router->get('foo', function () {
           return 'FOO_GLOBAL';
       });

    });

    $router->get('wp-json/posts', function () {

        return 'WP_JSON_ENDPOINT';

    });