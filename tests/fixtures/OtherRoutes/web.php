<?php


    declare(strict_types = 1);

    use Tests\stubs\TestApp;
    use WPMvc\Routing\Router;

    $router = TestApp::resolve(Router::class);

    $router->get('foo', function () {

        return 'foo-other-route';

    });

    $router->get('web-other', function () {

        return 'web-other';

    });