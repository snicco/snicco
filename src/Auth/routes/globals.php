<?php


    declare(strict_types = 1);

    /** @var Router $router */

    use WPEmerge\Routing\Router;

    $router->get('/wp-login.php', function (\WPEmerge\Http\ResponseFactory $response_factory) {

        return $response_factory->redirect()->toRoute('login', 301);

    });
